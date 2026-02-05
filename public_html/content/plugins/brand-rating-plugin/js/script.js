jQuery(document).ready(function($) {
  let items = $('#brp-container .brp-item');
  let index = 0;

  // Initially hide all items except the first one
  items.removeClass('active').css({ 'top': '100px', 'opacity': 0 });
  $(items[0]).addClass('active').css({ 'top': '0px', 'opacity': 1 });

  function showNextItem() {
      // Hide the current item by sliding it up
      $(items[index]).removeClass('active').animate({ 'top': '-100px', 'opacity': 0 }, 600, function() {
          $(this).css({ 'top': '100px' }); // Reset its position

          // Move to the next item
          index = (index + 1) % items.length;

          // Show the next item
          $(items[index]).addClass('active').animate({ 'top': '0px', 'opacity': 1 }, 600);
      });
  }

  // Rotate every 5 seconds
  setInterval(showNextItem, 5000);
});

document.addEventListener('DOMContentLoaded', function () {
  const navLinks = document.querySelectorAll('.nav-link a'); // Simplified selector
  const sections = document.querySelectorAll('.inpage-section');
  const header = document.querySelector('header'); // Ensure header exists
  let lastScrollTop = 0;
  const scrollThreshold = 100; // Header hide/show threshold

  // Highlight active nav link based on scroll position
  function updateActiveLink() {
      let currentSectionId = '';

      // Check which section is in the viewport
      sections.forEach(section => {
          const rect = section.getBoundingClientRect();
          if (rect.top <= window.innerHeight / 2 && rect.bottom >= window.innerHeight / 2) {
              currentSectionId = section.id;
          }
      });

      // Update nav link classes
      navLinks.forEach(link => {
          link.classList.remove('active');
          if (link.getAttribute('href')?.substring(1) === currentSectionId) {
            link.classList.add('active');
        }
      });
  }

  // Smooth scroll on nav link click
  navLinks.forEach(link => {
      link.addEventListener('click', function (e) {
          e.preventDefault();
          const targetId = this.getAttribute('href').substring(1);
          const targetSection = document.getElementById(targetId);
          targetSection.scrollIntoView({ behavior: 'smooth' });
      });
  });

  // Show/hide header on scroll
  window.addEventListener("scroll", function () {
      let currentScroll = window.pageYOffset || document.documentElement.scrollTop;
      if (currentScroll > lastScrollTop && currentScroll > scrollThreshold) {
          header.classList.add("hidden-header"); // Hide header
      } else if (currentScroll < lastScrollTop) {
          header.classList.remove("hidden-header"); // Show header
      }
      lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
      updateActiveLink(); // Update nav link highlighting on scroll
  });

  // Initial active link highlight
  updateActiveLink();
});
