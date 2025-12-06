var cfgWarningModal = {
  // 1 = aktif, 0 = nonaktif
  enabled: 1,

  // Judul modal
  title: "Peringatan / Warning",

  // Pesan warning (bisa gunakan HTML untuk format lebih bagus)
  message: `
    <p>Selamat datang di layanan WiFi kami.</p>
    <br>
    <p>Harap diperhatikan:</p>
    <ul style="list-style: disc; padding-left: 20px; margin-top: 10px;">
      <li>Gunakan koneksi dengan bijak</li>
      <li>Dilarang mengakses konten ilegal</li>
      <li>Bandwidth dibatasi untuk penggunaan yang adil</li>
    </ul>
  `,

  // Teks tombol close
  buttonText: "Saya Mengerti",

  // Style konfigurasi (opsional, bisa diubah sesuai kebutuhan)
  style: {
    // Background overlay (gelap di belakang modal)
    overlayBg: "rgba(0, 0, 0, 0.8)",

    // Background modal
    modalBg: "#1e293b",

    // Warna teks
    textColor: "#f1f5f9",

    // Warna tombol
    buttonBg: "#3b82f6",
    buttonHoverBg: "#2563eb",
    buttonTextColor: "#ffffff",
  }
};
