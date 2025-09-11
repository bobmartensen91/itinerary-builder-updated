<?php
require 'includes/auth.php';
require 'includes/db.php';
include 'includes/header.php';
?>
<h2 class="mb-4">Tours from WordPress REST API</h2>

<!-- Add controls -->
<div class="mb-3 d-flex gap-2 align-items-center">
  <button id="refresh-btn" class="btn btn-outline-primary">
    <i class="fas fa-sync-alt"></i> Refresh
  </button>
  <button class="btn btn-success" onclick="tourManager.saveAllPages()">
    <i class="fas fa-save"></i> Save All
  </button>
  <div class="form-check">
    <input class="form-check-input" type="checkbox" id="show-saved-only">
    <label class="form-check-label" for="show-saved-only">
      Show saved only
    </label>
  </div>
  <div class="ms-auto">
    <small class="text-muted">Total: <span id="total-count">0</span> | Showing: <span id="showing-count">0</span></small>
  </div>
</div>
<!-- Progress bar -->
<div class="progress" style="height: 25px; display:none;">
    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
         role="progressbar" style="width:0%">0%</div>
</div>
<table class="table table-bordered table-hover">
  <thead class="table-dark">
    <tr>
      <th style="width: 20%">Title</th>
      <th style="width: 30%">Description</th>
      <th style="width: 15%">Featured Image</th>
      <th style="width: 20%">Gallery</th>
      <th style="width: 10%">Action</th>
      <th style="width: 5%">Status</th>
    </tr>
  </thead>
  <tbody id="api-pages-table">
    <tr>
      <td colspan="6" class="text-center">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        Loading pages...
      </td>
    </tr>
  </tbody>
</table>

<script>
class TourManager {
  constructor() {
    this.cache = {
      pages: null,
      savedMap: null,
      mediaCache: new Map(),
      lastFetch: 0
    };
    this.CACHE_DURATION = 5 * 60 * 1000; // 5 minutes
    this.API_BASE = 'https://turer.vietnam-rejser.dk/wp-json/wp/v2';
    this.EXCLUDED_PAGES = [50];
    this.activeRequests = new Set();
    
    this.init();
  }

  init() {
    this.bindEvents();
    this.loadPages();
  }

  bindEvents() {
    document.getElementById('refresh-btn').addEventListener('click', () => {
      this.clearCache();
      this.loadPages();
    });

    document.getElementById('show-saved-only').addEventListener('change', (e) => {
      this.filterPages(e.target.checked);
    });
  }

  clearCache() {
    this.cache.pages = null;
    this.cache.savedMap = null;
    this.cache.mediaCache.clear();
    this.cache.lastFetch = 0;
  }

  // Utility functions
  decodeHTML(html) {
    const temp = document.createElement('div');
    temp.innerHTML = html || '';
    return temp.textContent || temp.innerText || '';
  }

  htmlToText(html) {
    if (!html) return '';
    const temp = document.createElement('div');
    temp.innerHTML = html;
    
    temp.querySelectorAll('br').forEach(el => el.replaceWith('\n'));
    temp.querySelectorAll('p').forEach(el => {
      const br = document.createTextNode('\n');
      el.after(br);
    });

    return temp.textContent.trim();
  }

  showLoading(message = 'Loading...') {
    const tableBody = document.getElementById('api-pages-table');
    tableBody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          ${message}
        </td>
      </tr>
    `;
  }

  showError(message) {
    const tableBody = document.getElementById('api-pages-table');
    tableBody.innerHTML = `
      <tr>
        <td colspan="6" class="text-center text-danger">
          <i class="fas fa-exclamation-triangle"></i> ${message}
        </td>
      </tr>
    `;
  }

  updateCounts(total, showing) {
    document.getElementById('total-count').textContent = total;
    document.getElementById('showing-count').textContent = showing;
  }

  // Fetch saved pages map with caching
  async fetchSavedMap(useCache = true) {
    if (useCache && this.cache.savedMap && 
        Date.now() - this.cache.lastFetch < this.CACHE_DURATION) {
      return this.cache.savedMap;
    }

    try {
      const resp = await fetch('api_tours_get.php');
      if (!resp.ok) {
        console.error('api_tours_get.php returned HTTP', resp.status);
        return {};
      }
      
      const list = await resp.json();
      const map = {};
      
      list.forEach(row => {
        if (row && typeof row.wp_id !== 'undefined') {
          map[Number(row.wp_id)] = Number(row.id);
        }
      });

      this.cache.savedMap = map;
      return map;
    } catch (err) {
      console.error('fetchSavedMap error', err);
      return {};
    }
  }

  // Batch fetch media with caching and concurrent limit
  async fetchMediaBatch(mediaIds) {
    const MAX_CONCURRENT = 3;
    const uncachedIds = mediaIds.filter(id => !this.cache.mediaCache.has(id));
    
    if (uncachedIds.length === 0) {
      return mediaIds.reduce((acc, id) => {
        acc[id] = this.cache.mediaCache.get(id);
        return acc;
      }, {});
    }

    const batches = [];
    for (let i = 0; i < uncachedIds.length; i += MAX_CONCURRENT) {
      batches.push(uncachedIds.slice(i, i + MAX_CONCURRENT));
    }

    const results = {};
    
    for (const batch of batches) {
      const promises = batch.map(async (id) => {
        if (this.activeRequests.has(`media-${id}`)) {
          return null;
        }
        
        this.activeRequests.add(`media-${id}`);
        
        try {
          const resp = await fetch(`${this.API_BASE}/media/${id}`);
          if (resp.ok) {
            const media = await resp.json();
            const url = media.source_url || '';
            this.cache.mediaCache.set(id, url);
            return { id, url };
          }
        } catch (err) {
          console.error(`Error fetching media ${id}:`, err);
        } finally {
          this.activeRequests.delete(`media-${id}`);
        }
        
        return { id, url: '' };
      });

      const batchResults = await Promise.allSettled(promises);
      batchResults.forEach(result => {
        if (result.status === 'fulfilled' && result.value) {
          results[result.value.id] = result.value.url;
        }
      });
    }

    mediaIds.forEach(id => {
      if (this.cache.mediaCache.has(id)) {
        results[id] = this.cache.mediaCache.get(id);
      }
    });

    return results;
  }

  // ✅ Main load function (robust pagination)
  async loadPages() {
    this.showLoading();

    try {
      const savedMap = await this.fetchSavedMap();

      const PER_PAGE = 100; // WP max
      let allPages = [];
      let pageNum = 1;
      const MAX_PAGE_GUARD = 50; // safety guard to avoid infinite loop

      while (pageNum <= MAX_PAGE_GUARD) {
        const resp = await fetch(
          `${this.API_BASE}/pages?per_page=${PER_PAGE}&page=${pageNum}&exclude=${this.EXCLUDED_PAGES.join(',')}`
        );

        if (!resp.ok) {
          throw new Error(`HTTP ${resp.status}: ${resp.statusText}`);
        }

        const pages = await resp.json();
        if (!Array.isArray(pages) || pages.length === 0) break;

        allPages = allPages.concat(pages);

        // If this page contained fewer items than PER_PAGE, we've reached the last page
        if (pages.length < PER_PAGE) break;

        pageNum++;
      }

      // Cache results
      this.cache.pages = allPages;
      this.cache.lastFetch = Date.now();

      // Collect media IDs
      const mediaIds = allPages
        .filter(page => page.featured_media && page.featured_media > 0)
        .map(page => page.featured_media);

      // Fetch media in batch
      const mediaResults = await this.fetchMediaBatch(mediaIds);

      // Render and update counts
      await this.renderPages(allPages, savedMap, mediaResults);
      this.updateCounts(allPages.length, allPages.length);

      // If "show saved only" is checked, apply filter
      const showSavedOnlyChecked = document.getElementById('show-saved-only').checked;
      if (showSavedOnlyChecked) {
        this.filterPages(true);
      }

    } catch (err) {
      console.error("loadPages error:", err);
      this.showError(`Error loading pages: ${err.message}`);
    }
  }

  // ✅ Fixed renderPages (no fragment/timer bug)
  async renderPages(pages, savedMap, mediaResults = {}) {
    const tableBody = document.getElementById('api-pages-table');
    // build document fragment synchronously (no delayed appends)
    const fragment = document.createDocumentFragment();

    for (const page of pages) {
      try {
        const row = await this.createPageRow(page, savedMap, mediaResults);
        fragment.appendChild(row);
      } catch (err) {
        console.error('Error creating row for page', page?.id, err);
        // skip this row but continue rendering others
      }
    }

    // Replace table body once
    tableBody.innerHTML = '';
    tableBody.appendChild(fragment);
  }

  // Create page row with improved structure
  async createPageRow(page, savedMap, mediaResults) {
    const localId = savedMap[page.id] || null;
    const isSaved = !!localId;

    const tr = document.createElement('tr');
    tr.dataset.pageId = page.id;
    tr.dataset.saved = isSaved;
    
    if (isSaved) tr.classList.add('table-success');

    // Get description
    let rawDescription = '';
    try {
      if (page.acf?.description?.trim()) {
        rawDescription = page.acf.description;
      } else if (page.content?.rendered) {
        rawDescription = page.content.rendered;
      } else {
        rawDescription = '(No description available)';
      }
    } catch (err) {
      rawDescription = '(No description available)';
    }

    const description = this.htmlToText(rawDescription).substring(0, 300);

    // Title cell
    const titleCell = this.createTitleCell(page, localId, isSaved);
    tr.appendChild(titleCell);

    // Description cell
    const descCell = this.createDescriptionCell(description);
    tr.appendChild(descCell);

    // Featured image cell
    const featuredImgUrl = mediaResults[page.featured_media] || '';
    const imgCell = this.createImageCell(featuredImgUrl, page.title?.rendered || '');
    tr.appendChild(imgCell);

    // Gallery cell
    const galleryCell = this.createGalleryCell(page.acf);
    tr.appendChild(galleryCell);

    // Action cell
    const actionCell = this.createActionCell(page, rawDescription, featuredImgUrl, page.acf);
    tr.appendChild(actionCell);

    // Status cell
    const statusCell = this.createStatusCell(isSaved);
    tr.appendChild(statusCell);

    return tr;
  }

  createTitleCell(page, localId, isSaved) {
    const td = document.createElement('td');
    
    if (isSaved) {
      const a = document.createElement('a');
      a.href = `api_tours_view.php?id=${encodeURIComponent(localId)}`;
      a.textContent = this.decodeHTML(page.title?.rendered || '');
      a.className = 'text-decoration-none';
      td.appendChild(a);
    } else {
      td.textContent = this.decodeHTML(page.title?.rendered || '');
    }
    
    return td;
  }

  createDescriptionCell(description) {
    const td = document.createElement('td');
    td.style.whiteSpace = 'pre-wrap';
    td.style.maxHeight = '100px';
    td.style.overflow = 'hidden';
    td.textContent = description + (description.length >= 100 ? '...' : '');
    return td;
  }

  createImageCell(featuredImgUrl, altText) {
    const td = document.createElement('td');
    
    if (featuredImgUrl) {
      const img = document.createElement('img');
      img.width = 80;
      img.height = 60;
      img.src = featuredImgUrl;
      img.alt = this.decodeHTML(altText);
      img.style.objectFit = 'cover';
      img.className = 'rounded';
      img.loading = 'lazy';
      td.appendChild(img);
    } else {
      td.innerHTML = '<span class="text-muted">No image</span>';
    }
    
    return td;
  }

  createGalleryCell(acf) {
    const td = document.createElement('td');
    td.className = 'image-gallery';
    
    if (acf) {
      let galleryImages = [];
      for (const key in acf) {
        const value = acf[key];
        if (Array.isArray(value) && value.length && value[0]?.full_image_url) {
          galleryImages = value.slice(0, 4).map(img => img.full_image_url);
          break;
        }
      }
      
      galleryImages.forEach(url => {
        const img = document.createElement('img');
        img.width = 50;
        img.height = 40;
        img.src = url;
        img.className = 'me-1 mb-1 rounded';
        img.style.objectFit = 'cover';
        img.loading = 'lazy';
        td.appendChild(img);
      });
    }
    
    return td;
  }

  createActionCell(page, rawDescription, featuredImgUrl, acf) {
    const td = document.createElement('td');
    const btn = document.createElement('button');
    btn.className = 'btn btn-primary btn-sm save-btn w-100';
    btn.textContent = 'Save';
    btn.onclick = () => this.savePage(page, rawDescription, featuredImgUrl, acf, btn);
    td.appendChild(btn);
    return td;
  }

  createStatusCell(isSaved) {
    const td = document.createElement('td');
    td.className = 'status-cell text-center';
    
    const indicator = document.createElement('span');
    indicator.className = `status-indicator ${isSaved ? 'status-saved' : 'status-new'}`;
    indicator.title = isSaved ? 'Already saved' : 'New';
    
    td.appendChild(indicator);
    return td;
  }

  // Improved save function with better error handling
  async savePage(page, rawDescription, featuredImgUrl, acf, btn) {
    const row = btn.closest('tr');
    const statusCell = row.querySelector('.status-cell');
    const indicator = statusCell.querySelector('.status-indicator');

    btn.disabled = true;
    btn.textContent = 'Saving...';
    indicator.className = 'status-indicator status-saving';

    try {
      const galleryImages = this.extractGalleryImages(acf);
      const formData = new FormData();

      formData.append('wp_id', page.id);
      formData.append('title', page.title.rendered);
      formData.append('description', rawDescription);
      formData.append('featured_image', featuredImgUrl);
      galleryImages.forEach((url, i) => formData.append('image' + (i + 1), url));
      formData.append('acf', JSON.stringify(acf || {}));

      const resp = await fetch('api_tours_save.php', {
        method: 'POST',
        body: formData
      });

      // Always read body ONCE
      const text = await resp.text();
      let result;
      try {
        result = JSON.parse(text);
      } catch (err) {
        console.error("Invalid JSON from server:", text);
        throw new Error('Invalid server response');
      }

      if (result.success) {
        // Update cache
        this.cache.savedMap = null; // Force refresh
        const newSavedMap = await this.fetchSavedMap(false);
        const newLocalId = newSavedMap[page.id];

        // Update UI
        row.classList.add('table-success');
        row.dataset.saved = 'true';
        indicator.className = 'status-indicator status-saved';
        indicator.title = 'Saved';

        // Update title cell if needed
        if (newLocalId) {
          const titleCell = row.querySelector('td:first-child');
          titleCell.innerHTML = '';
          const a = document.createElement('a');
          a.href = `api_tours_view.php?id=${encodeURIComponent(newLocalId)}`;
          a.textContent = this.decodeHTML(page.title.rendered);
          a.className = 'text-decoration-none';
          titleCell.appendChild(a);
        }

        btn.textContent = 'Saved ✓';
        setTimeout(() => {
          btn.textContent = 'Update';
          btn.disabled = false;
        }, 2000);

      } else {
        throw new Error(result.message || 'Unknown error');
      }

    } catch (err) {
      console.error('Save error:', err);
      indicator.className = 'status-indicator status-error';
      indicator.title = 'Error: ' + err.message;
      btn.textContent = 'Error - Retry';
      setTimeout(() => {
        btn.textContent = 'Save';
        btn.disabled = false;
      }, 3000);
    }
  }

  // ✅ Save all pages with progress bar
  async saveAllPages() {
    const rows = document.querySelectorAll('#api-pages-table tr[data-page-id]');
    if (!rows.length) return;

    const saveAllBtn = document.querySelector('button.btn-success'); // your "Save All" button
    saveAllBtn.disabled = true;
    saveAllBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    // Show progress bar
    document.querySelector('.progress').style.display = 'block';
    const progressBar = document.getElementById('progressBar');
    progressBar.style.width = '0%';
    progressBar.innerText = '0%';

    let successCount = 0;
    let errorCount = 0;
    let saved = 0;

    for (const row of rows) {
      const btn = row.querySelector('.save-btn');
      if (!btn) continue;

      try {
        // get page data from row
        const pageId = row.dataset.pageId;
        const page = this.cache.pages.find(p => p.id == pageId);
        if (!page) continue;

        const featuredImgUrl = row.querySelector('td:nth-child(3) img')?.src || '';
        const rawDescription = page.acf?.description || page.content?.rendered || '';
        const acf = page.acf || {};

        // directly call savePage() instead of btn.click()
        await this.savePage(page, rawDescription, featuredImgUrl, acf, btn);
        successCount++;
      } catch (err) {
        console.error('Error saving row:', err);
        errorCount++;
      }

      saved++;
      const percent = Math.round((saved / rows.length) * 100);
      progressBar.style.width = percent + '%';
      progressBar.innerText = percent + '%';
    }

    saveAllBtn.innerHTML = `✅ Saved ${successCount} | ❌ ${errorCount}`;
    setTimeout(() => {
      saveAllBtn.innerHTML = '<i class="fas fa-save"></i> Save All';
      saveAllBtn.disabled = false;
      document.querySelector('.progress').style.display = 'none';
    }, 4000);
  }

  extractGalleryImages(acf) {
    if (!acf) return [];
    
    for (const key in acf) {
      const value = acf[key];
      if (Array.isArray(value) && value.length && value[0]?.full_image_url) {
        return value.slice(0, 4).map(img => img.full_image_url);
      }
    }
    return [];
  }

  // Filter pages by saved status
  filterPages(showSavedOnly) {
    const rows = document.querySelectorAll('#api-pages-table tr[data-page-id]');
    let visibleCount = 0;
    
    rows.forEach(row => {
      const isSaved = row.dataset.saved === 'true';
      const shouldShow = !showSavedOnly || isSaved;
      
      row.style.display = shouldShow ? '' : 'none';
      if (shouldShow) visibleCount++;
    });
    
    this.updateCounts(rows.length, visibleCount);
  }
}

// Initialize when DOM is ready
// ✅ Make TourManager available globally
document.addEventListener('DOMContentLoaded', () => {
  window.tourManager = new TourManager();
});
</script>

<?php include 'includes/footer.php'; ?>
