let lastScrollDirection = 'down';
let offsetStart = 0;
let offsetEnd = 0;
let isLoading = false;
let searchQuery = '';
const PAGE_SIZE = 100;
const MAX_ROWS = 200;

let leadModalInstance; // Declare globally

// Function to handle opening and populating the modal
function editInstall(code) {
  console.log('Editing lead with code:', code);
  if (!leadModalInstance) {
    console.error('Modal not initialized yet.');
    return;
  }

  const leadForm = document.getElementById('leadForm');
  leadForm.reset();
  document.getElementById('leadId').value = '';

  if (code && code !== 'L-NA') {
    fetch(`lead_fetch.php?leadId=${encodeURIComponent(code)}`)
      .then(res => {
        if (!res.ok) throw new Error("Failed to fetch lead details");
        return res.json();
      })
      .then(data => {
        if (!data || !data.id) {
          M.toast({ html: "Lead not found", classes: "red" });
          return;
        }

        // Fill modal fields
        document.getElementById('leadId').value = data.id;
        document.getElementById('leadName').value = data.Name || '';
        document.getElementById('leadWhatsapp').value = data.whatsapp_number || '';
        document.getElementById('leadHdd').value = data.hdd_size || '';
        document.getElementById('leadNumCameras').value = data.num_cameras || '';
        document.getElementById('leadCameraResolution').value = data.camera_resolution || '';
        document.getElementById('leadDvrType').value = data.dvr_type || '';
        document.getElementById('leadAssign').value = data.Assign || '';
        document.getElementById('leadArea').value = data.Area || '';
        document.getElementById('leadFollowUp').value = data.Follow_up || '';
        document.getElementById('leadComments').value = data.comments || '';
        document.getElementById('leadMessage').value = data.Message || '';
        document.getElementById('leadMapLink').value = data.map_link || '';
        document.getElementById('leadQuote').value = data.quote || '';

        M.updateTextFields();
        leadModalInstance.open();
      })
      .catch(err => {
        console.error("Error fetching lead data:", err);
        M.toast({ html: "Error loading lead details", classes: "red" });
      });
  } else {
    // New lead
    M.updateTextFields();
    leadModalInstance.open();
  }
}


function createRow(row) {
  const el = document.createElement('div');
  el.className = 'col s12';

  // Use display_id field which is either MID or id
  const displayId = row.display_id || row.MID || row.id || '';
  const rid = row.id || '';
  const code = `L-${rid}`;
  const name = row.Name || '';
  const displayName = name || 'NAME';
  const nameClass = name ? 'lead-name' : 'lead-name-placeholder';
  const phone = row.whatsapp_number || '';
  const cams = row.num_cameras || '';
  const dvr = row.dvr_type || '';
  const hdd = row.hdd_size || '';
  const res = row.camera_resolution || '';
  const assign = row.Assign || '-';
  const area = row.Area || '-';
  const follow = row.Follow_up || '-';
  const comment = row.comments || '-';
  const created = row.created_at || '-';
  const msg = row.Message || '-';
  const map = row.map_link || '';

  // Format date (dd mmm yy)
  let formattedDate = '-';
  if (created && created !== '-') {
    const dateObj = new Date(created);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const day = String(dateObj.getDate()).padStart(2, '0');
    const month = months[dateObj.getMonth()];
    const year = String(dateObj.getFullYear()).slice(-2);
    formattedDate = `${day} ${month} ${year}`;
  }

  // Format technical specs - replace dont-know with X
  const hddDisplay = (hdd && hdd.toLowerCase() !== 'dont-know') ? `<span class="spec-value">${hdd}</span>` : '<span class="spec-unknown">X</span>';
  const resDisplay = (res && res.toLowerCase() !== 'dont-know') ? `<span class="spec-value">${res}</span>` : '<span class="spec-unknown">X</span>';
  const camDisplay = (cams && cams.toString().toLowerCase() !== 'dont-know') ? `<span class="spec-value">${cams}</span>` : '<span class="spec-unknown">X</span>';
  const recDisplay = (dvr && dvr.toLowerCase() !== 'dont-know') ? `<span class="spec-value">${dvr}</span>` : '<span class="spec-unknown">X</span>';

  // Generate unique avatar pattern for this phone number (Copied logic from PHP file if needed, or assume simpler here. 
  // lead_list.js didn't have the avatar logic in the previous view. I will keep it simple or copy it?
  // The previous view of lead_list.js (lines 66-118) showed a much simpler createRow without avatar generation logic.
  // I should stick to the structure but maybe omitting the complex avatar logic if helper functions aren't in this file.
  // Actually, lead_list.js relies on helper functions not seen in the file view? No, I saw the whole file. It didn't have helper functions.
  // So lead_list.js version is a simplified one. I will upgrade it to match the PHP version's structure but maybe without the complex avatar pattern if I can't easily add the helper function. 
  // However, for consistency, I should probably add the helper function or just use a placeholder.
  // Let's use the new HTML structure but with a placeholder for the avatar part if the helper isn't available.
  // Wait, if I'm "modernizing", I should probably make lead_list.js meaningful?
  // The user likely uses lead_list.php inline script.
  // I will update it to match the structure, but since `generateAvatarPattern` is missing in lead_list.js, I will skip the avatar block or use a static one.

  // Actually, let's just make it look good.
  const iconClassName = 'fa-user'; // Default
  const shapeClass = 'avatar-circle';
  const avatarPattern = { shape: '#ccc', bgPattern: 'none' };
  const patternStyle = '';

  // ✅ Build the quote parameter string
  const quoteParam = encodeURIComponent(`${phone}|${cams}|${dvr}|${hdd}|${res}|${name}|${code}`);
  const quoteUrl = `quote.php?quote=${quoteParam}`;

  el.innerHTML = `
        <div class="card lead-card"
             data-created-at="${created}"
             data-lead-id="${rid}">
          
          <!-- Portion 1: Lead Info -->
          <div class="lead-info-section">
             <!-- Placeholder Avatar -->
             <div class="lead-qr-code">
               <div class="qr-placeholder">
                 <div class="avatar-shape avatar-circle" style="background-color: #ffffff; border: 1px solid #999;">
                   <i class="fa fa-user avatar-icon" style="color: #999; font-size: 16px;"></i>
                 </div>
               </div>
            </div>

            <div class="lead-info-content">
              <div class="lead-header">
                <h3 class="${nameClass}">${displayName}</h3>
                ${assign && assign !== '-' ? `<span class="assign-badge">${assign}</span>` : ''}
              </div>

              <div class="lead-meta-row">
                ${phone ? `<span class="lead-phone"><i class="fa fa-whatsapp"></i> ${phone}</span>` : ''}
                ${area && area !== '-' ? `<span class="lead-phone"><i class="fa fa-map-marker-alt"></i> ${area}</span>` : ''}
                ${follow && follow !== '-' ? `<span class="lead-phone"><i class="fa fa-calendar-alt"></i> ${follow}</span>` : ''}
              </div>

              <div class="lead-specs">
                <span class="spec-tag">HDD <span class="spec-value">${hddDisplay}</span></span>
                <span class="spec-tag">RES <span class="spec-value">${resDisplay}</span> x <span class="spec-value">${camDisplay}</span></span>
                <span class="spec-tag">DVR <span class="spec-value">${recDisplay}</span></span>
              </div>
            </div>
          </div>
          
          <!-- Portion 2: Expandable Form -->
          <div class="lead-form-section"></div>
          
          <!-- Portion 3: Controls -->
          <div class="lead-controls-section">
            <div class="lead-controls-left">
               <span class="lead-phone" style="font-size: 12px;">${formattedDate}</span>
            </div>
            <div class="lead-actions">
              <a href="#" class="action-link action-edit edit-lead-btn" data-code="${code}">
                <i class="fa fa-pen"></i> Edit
              </a>
              <a href="#" class="action-link action-quote open-quote" data-quote-url="${quoteUrl}">
                <i class="fa fa-file-invoice"></i> Quote
              </a>
            </div>
          </div>
        </div>
      `;

  return el;
}



function fetchRows(direction = 'down') {
  if (isLoading) return;
  isLoading = true;
  status.innerText = 'Loading...';

  let fetchOffset;
  if (direction === 'down') {
    fetchOffset = offsetEnd;
  } else {
    if (offsetStart === 0) {
      status.innerText = '';
      isLoading = false;
      return;
    }
    fetchOffset = Math.max(0, offsetStart - PAGE_SIZE);
  }

  fetch(`lead_fetch.php?offset=${fetchOffset}&search=${encodeURIComponent(searchQuery)}`)
    .then(res => {
      if (!res.ok) throw new Error("Server error");
      return res.json();
    })
    .then(data => {
      if (!Array.isArray(data) || data.length === 0) {
        status.innerText = 'No more records.';
        isLoading = false;
        return;
      }

      if (direction === 'down') {
        data.forEach(row => {
          const el = createRow(row);
          container.insertBefore(el, bottomSentinel);
          offsetEnd++;
        });
      } else {
        const firstRowBefore = topSentinel.nextElementSibling;
        const prevTop = firstRowBefore ? firstRowBefore.getBoundingClientRect().top : 0;

        const frag = document.createDocumentFragment();
        for (let i = data.length - 1; i >= 0; i--) {
          frag.appendChild(createRow(data[i]));
        }
        container.insertBefore(frag, topSentinel.nextSibling);

        requestAnimationFrame(() => {
          if (firstRowBefore) {
            const newTop = firstRowBefore.getBoundingClientRect().top;
            const delta = newTop - prevTop;
            window.scrollBy(0, delta);
          }
        });

        offsetStart = Math.max(0, offsetStart - data.length);
      }

      trimExcessRows();
      status.innerText = '';
      isLoading = false;
    })
    .catch(err => {
      console.error(err);
      status.innerText = 'Error loading records.';
      isLoading = false;
    });
}

function trimExcessRows() {
  const items = Array.from(container.querySelectorAll('.lead-card'));
  if (items.length <= MAX_ROWS) return;

  const extra = items.length - MAX_ROWS;

  if (offsetEnd - offsetStart > MAX_ROWS) {
    if (lastScrollDirection === 'down') {
      for (let i = 0; i < extra; i++) {
        items[i].remove();
        offsetStart++;
      }
    }
    else {
      for (let i = 0; i < extra; i++) {
        items[items.length - 1 - i].remove();
        offsetEnd--;
      }
    }
  }
}

function resetStateAndReload() {
  offsetStart = 0;
  offsetEnd = 0;
  container.querySelectorAll('.lead-card').forEach(el => el.remove());
  fetchRows('down');
}


let container, topSentinel, bottomSentinel, searchInput, clearBtn, status; // Declare variables here

document.addEventListener('DOMContentLoaded', function () {
  console.log('DOMContentLoaded fired.');

  // Assign elements inside DOMContentLoaded
  container = document.getElementById('container');
  topSentinel = document.getElementById('top-sentinel');
  bottomSentinel = document.getElementById('bottom-sentinel');
  searchInput = document.getElementById('searchInput');
  clearBtn = document.getElementById('clearBtn');
  status = document.getElementById('status');

  console.log('Container for event delegation:', container);
  console.log('topSentinel:', topSentinel);
  console.log('bottomSentinel:', bottomSentinel);
  console.log('searchInput:', searchInput);
  console.log('clearBtn:', clearBtn);
  console.log('status:', status);

  const leadModalElement = document.getElementById('leadModal');
  console.log('leadModalElement:', leadModalElement);
  leadModalInstance = M.Modal.init(leadModalElement);
  console.log('leadModalInstance after init:', leadModalInstance);
  const leadForm = document.getElementById('leadForm');
  const saveLeadBtn = document.getElementById('saveLeadBtn');

  // Event delegation for 'Edit Lead' buttons
  if (container) { // Check if container is not null before adding event listener
    container.addEventListener('click', function (event) {
      if (event.target.classList.contains('edit-lead-btn')) {
        event.preventDefault(); // Prevent default link behavior
        const code = event.target.dataset.code;
        editInstall(code);
      }
    });
    console.log('Click event listener set up on container.');
  } else {
    console.error('Container element not found, cannot set up click listener.');
  }

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      searchQuery = searchInput.value.trim();
      resetStateAndReload();
    });
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      searchInput.value = '';
      searchQuery = '';
      resetStateAndReload();
    });
  }

  if (saveLeadBtn) {
    saveLeadBtn.addEventListener('click', function () {
      const formData = new FormData(leadForm);
      const leadData = {};
      for (let [key, value] of formData.entries()) {
        leadData[key] = value;
      }

      console.log('Saving lead data:', leadData);

      fetch('lead_save.php', {
        method: 'POST',
        body: JSON.stringify(leadData),
        headers: { 'Content-Type': 'application/json' }
      })
        .then(async response => {
          const text = await response.text();
          console.log('Raw response text:', text);

          if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

          let data;
          try {
            data = JSON.parse(text);
          } catch (e) {
            throw new Error('Invalid JSON: ' + text);
          }

          if (data.success) {
            M.toast({ html: data.message || 'Lead saved successfully', classes: 'green' });
            leadModalInstance.close();

            // ✅ update that card directly
            updateSingleCard(leadData, data.id || leadData.leadId);
          } else {
            M.toast({ html: data.message || 'Failed to save lead', classes: 'red' });
          }
        })

        .catch(error => {
          console.error('Fetch error:', error);
          M.toast({ html: 'Error saving lead. Please try again.', classes: 'red' });
        });
    });
  }
  // ✅ Update only the edited card without reloading the page
  function updateSingleCard(updatedData, leadId) {
    const code = `L-${leadId}`;
    const existingCard = container.querySelector(`.edit-lead-btn[data-code="${code}"]`);
    if (!existingCard) {
      console.warn('No matching card found to update.');
      return;
    }

    // Recreate the updated card using existing function
    const newCard = createRow({
      id: leadId,
      Name: updatedData.Name,
      whatsapp_number: updatedData.whatsapp_number,
      hdd_size: updatedData.hdd_size,
      num_cameras: updatedData.num_cameras,
      camera_resolution: updatedData.camera_resolution,
      dvr_type: updatedData.dvr_type,
      Assign: updatedData.Assign,
      Area: updatedData.Area,
      Follow_up: updatedData.Follow_up,
      comments: updatedData.comments,
      Message: updatedData.Message,
      map_link: updatedData.map_link,
      quote: updatedData.quote,
      created_at: updatedData.created_at || new Date().toISOString()
    });

    const cardContainer = existingCard.closest('.col');
    cardContainer.replaceWith(newCard);

    // Highlight the updated card
    newCard.classList.add('highlight-card');
    setTimeout(() => {
      newCard.classList.remove('highlight-card');
    }, 4000); // highlight for 4 seconds
  }



  // Initialize Materialize components that need it
  M.updateTextFields(); // For input labels

  // Load initial leads
  fetchRows('down');
});

// Intersection Observer setup (remains outside DOMContentLoaded as it uses globally declared variables)
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      if (entry.target.id === 'bottom-sentinel') {
        lastScrollDirection = 'down';
        fetchRows('down');
      }
      if (entry.target.id === 'top-sentinel') {
        lastScrollDirection = 'up';
        fetchRows('up');
      }
    }
  });
}, {
  root: null,
  rootMargin: '200px 0px',
  threshold: 0.01
});

// Observe sentinels after they are assigned in DOMContentLoaded
document.addEventListener('DOMContentLoaded', function () {
  if (topSentinel) observer.observe(topSentinel);
  if (bottomSentinel) observer.observe(bottomSentinel);
  // Quote Modal logic
  const quoteModal = document.getElementById('quoteModal');
  const quoteIframe = document.getElementById('quoteIframe');
  const closeQuoteModal = document.getElementById('closeQuoteModal');

  // Event delegation: detect click on Quote link
  container.addEventListener('click', (e) => {
    if (e.target.closest('.open-quote')) {
      e.preventDefault();
      const quoteUrl = e.target.closest('.open-quote').dataset.quoteUrl;
      openQuoteModal(quoteUrl);
    }
  });

  // Function to open the quote modal
  function openQuoteModal(url) {
    quoteIframe.src = url;
    quoteModal.classList.add('active');
    document.documentElement.style.overflow = 'hidden';
    document.body.style.height = '100%';
    document.body.style.position = 'fixed';
  }

  // Close button event
  closeQuoteModal.addEventListener('click', () => {
    quoteModal.classList.remove('active');
    quoteIframe.src = '';
    document.documentElement.style.overflow = 'auto';
    document.body.style.height = 'auto';
    document.body.style.position = 'static';

  });

  // Close when clicking outside the modal content
  quoteModal.addEventListener('click', (e) => {
    if (e.target === quoteModal) {
      quoteModal.classList.remove('active');
      quoteIframe.src = '';
      document.body.style.overflow = 'auto';
    }
  });

});


