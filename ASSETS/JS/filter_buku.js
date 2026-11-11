// Dropdown pencarian untuk filter kategori, penulis, dan penerbit.
document.addEventListener('DOMContentLoaded', function () {
    var endpoint = '/Aplikasi_Peminjaman_Buku/CONTROLLER/c_cari.php';

    // =================================================================
    // LOGIKA LAMA: Untuk tag <input list="..."> (Agar halaman lain tidak error)
    // =================================================================
    document.querySelectorAll('input[data-cari]').forEach(function (input) {
        var daftar = document.getElementById(input.getAttribute('list'));
        if (!daftar) return; // Lewati jika tidak ada datalist
        var timer = null;

        function muatPilihan() {
            var url = endpoint + '?jenis=' + encodeURIComponent(input.dataset.cari) +
                '&q=' + encodeURIComponent(input.value.trim());

            fetch(url)
                .then(function (respon) { return respon.json(); })
                .then(function (data) {
                    daftar.innerHTML = '';
                    data.forEach(function (nama) {
                        var opsi = document.createElement('option');
                        opsi.value = nama;
                        daftar.appendChild(opsi);
                    });
                })
                .catch(function () { /* abaikan jika gagal memuat */ });
        }

        input.addEventListener('focus', muatPilihan);
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(muatPilihan, 250);
        });
    });
});

// =================================================================
// LOGIKA BARU: Khusus untuk tag <select> bergaya elegan dengan Select2
// =================================================================
if (typeof jQuery !== 'undefined') {
    $(document).ready(function () {
        var endpoint = '/Aplikasi_Peminjaman_Buku/CONTROLLER/c_cari.php';

        $('.select2-ajax').each(function () {
            var jenisCari = $(this).data('cari');
            var placeholderText = $(this).data('placeholder') || 'Pilih/cari...';

            $(this).select2({
                width: '100%',
                placeholder: placeholderText,
                allowClear: true,
                language: {
                    noResults: function() { return "Data tidak ditemukan"; },
                    searching: function() { return "Mencari..."; }
                },
                ajax: {
                    url: endpoint,
                    dataType: 'json',
                    delay: 250, // Jeda ketik agar server tidak down
                    data: function (params) {
                        return {
                            jenis: jenisCari,
                            q: params.term || '' // Kata kunci yang diketik user
                        };
                    },
                    processResults: function (data) {
                        // Mengubah format array dari PHP menjadi format id & text untuk Select2
                        var results = data.map(function (item) {
                            return { id: item, text: item };
                        });
                        return { results: results };
                    },
                    cache: true
                }
            });
        });
    });
}