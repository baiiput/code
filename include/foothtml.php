
</div>
<!-- End of Main Content -->

<!-- Additional Scripts -->
<script>
// Initialize tooltips and other UI enhancements
$(document).ready(function() {
  // Add smooth scrolling
  $('a[href^="#"]').on('click', function(e) {
    e.preventDefault();
    const target = $(this.getAttribute('href'));
    if(target.length) {
      $('html, body').stop().animate({
        scrollTop: target.offset().top - 80
      }, 500);
    }
  });

  // Add loading state to buttons
  $('button[type="submit"], a.btn').on('click', function() {
    const btn = $(this);
    if(!btn.hasClass('no-loading')) {
      btn.data('original-text', btn.html());
      btn.html('<i class="fa fa-spinner fa-spin"></i> Loading...');
      btn.prop('disabled', true);

      // Re-enable after 3 seconds (fallback)
      setTimeout(function() {
        btn.html(btn.data('original-text'));
        btn.prop('disabled', false);
      }, 3000);
    }
  });

  // Add ripple effect to buttons
  $('.btn, .menu-link, .submenu-link').on('click', function(e) {
    const button = $(this);
    const ripple = $('<span class="ripple"></span>');
    const diameter = Math.max(button.outerWidth(), button.outerHeight());
    const radius = diameter / 2;

    ripple.css({
      width: diameter,
      height: diameter,
      left: e.pageX - button.offset().left - radius,
      top: e.pageY - button.offset().top - radius
    }).appendTo(button);

    setTimeout(function() {
      ripple.remove();
    }, 600);
  });
});

// Add custom styles for ripple effect
const style = document.createElement('style');
style.textContent = `
  .btn, .menu-link, .submenu-link {
    position: relative;
    overflow: hidden;
  }

  .ripple {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: scale(0);
    animation: ripple-animation 0.6s ease-out;
    pointer-events: none;
  }

  @keyframes ripple-animation {
    to {
      transform: scale(4);
      opacity: 0;
    }
  }

  /* Scrollbar styling */
  ::-webkit-scrollbar {
    width: 10px;
    height: 10px;
  }

  ::-webkit-scrollbar-track {
    background: #14171f;
  }

  ::-webkit-scrollbar-thumb {
    background: #2d3142;
    border-radius: 5px;
  }

  ::-webkit-scrollbar-thumb:hover {
    background: #3d4152;
  }

  /* Selection styling */
  ::selection {
    background: rgba(74, 158, 255, 0.3);
    color: #ffffff;
  }

  /* Focus styles */
  button:focus, a:focus, input:focus, select:focus, textarea:focus {
    outline: 2px solid rgba(74, 158, 255, 0.5);
    outline-offset: 2px;
  }

  /* Table responsive */
  .table-responsive {
    border-radius: 8px;
    overflow: hidden;
  }

  /* Cards enhancement */
  .card {
    transition: transform 0.2s, box-shadow 0.2s;
  }

  .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
  }

  /* Loading overlay */
  .loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
  }

  .loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid rgba(255, 255, 255, 0.1);
    border-top-color: #4a9eff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  @keyframes spin {
    to { transform: rotate(360deg); }
  }
`;
document.head.appendChild(style);

// Global loading function
window.showLoading = function() {
  if(!$('#globalLoading').length) {
    $('body').append(`
      <div id="globalLoading" class="loading-overlay">
        <div class="loading-spinner"></div>
      </div>
    `);
  }
};

window.hideLoading = function() {
  $('#globalLoading').fadeOut(function() {
    $(this).remove();
  });
};

// Auto-hide loading after page load
$(window).on('load', function() {
  hideLoading();
});

// Show loading on page unload
$(window).on('beforeunload', function() {
  showLoading();
});
</script>

</body>
</html>
