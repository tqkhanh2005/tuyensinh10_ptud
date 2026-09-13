<?php
/**
 * Global JavaScript Libraries & Setup
 */
?>
<script src="<?= asset('vendor/jquery/jquery.min.js') ?>"></script>
<script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script>
  // Handle 401 Unauthorized globally for all AJAX requests
  $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    if (jqxhr.status === 401) {
      window.location.href = '<?= url('/auth/login') ?>';
    }
  });

  let _globalConfirmCallback = null;
  /**
   * Hiển thị hộp thoại xác nhận chuyên nghiệp (Async Callback)
   */
  window.showConfirm = function(title, message, btnText, btnClass, callback) {
    $('#globalConfirmTitle').text(title || 'Xác nhận');
    $('#globalConfirmMessage').html(message || 'Bạn có chắc chắn thực hiện hành động này?');

    const $btn = $('#globalConfirmBtn');
    $btn.text(btnText || 'Đồng ý');
    $btn.removeClass().addClass('btn px-4 ' + (btnClass || 'btn-primary'));

    _globalConfirmCallback = callback;
    const modal = new bootstrap.Modal(document.getElementById('globalConfirmModal'));
    modal.show();
  };

  $(document).ready(function() {
    $('#globalConfirmBtn').on('click', function() {
      const modal = bootstrap.Modal.getInstance(document.getElementById('globalConfirmModal'));
      if (modal) modal.hide();
      if (typeof _globalConfirmCallback === 'function') {
        _globalConfirmCallback();
      }
    });
  });
</script>
