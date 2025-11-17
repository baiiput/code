    <!-- Main Footer -->
    <footer class="bg-light border-top py-3 mt-auto">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <strong>Copyright &copy; <?= date('Y') ?> <a href="#"><?= APP_NAME ?></a>.</strong>
                    All rights reserved.
                </div>
                <div class="col-md-6 text-end">
                    <b>Version</b> <?= APP_VERSION ?>
                </div>
            </div>
        </div>
    </footer>
</main>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Dark Mode Toggle
$(document).ready(function() {
    // Check if dark mode is enabled
    function isDarkMode() {
        return document.cookie.split(';').some((item) => item.trim().startsWith('dark_mode=1'));
    }

    // Update icon based on current mode
    function updateDarkModeIcon() {
        if (isDarkMode()) {
            $('#darkModeIcon').removeClass('fa-moon').addClass('fa-sun');
        } else {
            $('#darkModeIcon').removeClass('fa-sun').addClass('fa-moon');
        }
    }

    // Initialize icon
    updateDarkModeIcon();

    // Toggle dark mode
    $('#darkModeToggle').click(function(e) {
        e.preventDefault();

        if (isDarkMode()) {
            // Disable dark mode
            document.cookie = 'dark_mode=0; path=/; max-age=' + (365 * 24 * 60 * 60);
            $('html').attr('data-bs-theme', 'light');
        } else {
            // Enable dark mode
            document.cookie = 'dark_mode=1; path=/; max-age=' + (365 * 24 * 60 * 60);
            $('html').attr('data-bs-theme', 'dark');
        }

        updateDarkModeIcon();
    });

    // Initialize DataTable
    if ($('.datatable').length > 0) {
        $('.datatable').DataTable({
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json"
            }
        });
    }

    // Initialize Select2
    if ($('.select2').length > 0) {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Load badge counts for sidebar
    loadBadgeCounts();
});

// Load badge counts
function loadBadgeCounts() {
    $.ajax({
        url: '<?= APP_URL ?>/api/get-counts.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#badge-jatuh-tempo').text(response.counts.jatuh_tempo || 0);
                $('#badge-proses').text(response.counts.proses || 0);
                $('#badge-segera').text(response.counts.segera || 0);
                $('#badge-observasi').text(response.counts.observasi || 0);
            }
        },
        error: function() {
            console.log('Failed to load badge counts');
        }
    });
}

// Confirm delete
function confirmDelete(url, message) {
    message = message || 'Apakah Anda yakin ingin menghapus data ini?';

    Swal.fire({
        title: 'Konfirmasi',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}

// Format rupiah
function formatRupiah(angka) {
    var number_string = angka.toString().replace(/[^,\d]/g, ''),
        split = number_string.split(','),
        sisa = split[0].length % 3,
        rupiah = split[0].substr(0, sisa),
        ribuan = split[0].substr(sisa).match(/\d{3}/gi);

    if (ribuan) {
        var separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }

    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    return 'Rp ' + rupiah;
}
</script>

<?php if (isset($additionalJS)): ?>
    <?= $additionalJS ?>
<?php endif; ?>

</body>
</html>
