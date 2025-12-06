var cfgSlide = {
  status: 0,  // 0 = slider disabled, use static background instead

  // Static background configuration
  staticBackground: {
    enabled: 1,  // 1 = use static background

    // Option 1: Use image (jika ada file gambar)
    // imagePath: "./assets/background.jpg",

    // Option 2: Use gradient (AKTIF - karena tidak ada gambar)
    useGradient: 1,
    gradient: "linear-gradient(135deg, #667eea 0%, #764ba2 100%)",
    // Gradient options:
    // "linear-gradient(135deg, #667eea 0%, #764ba2 100%)" // Purple
    // "linear-gradient(135deg, #f093fb 0%, #f5576c 100%)" // Pink
    // "linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)" // Blue
    // "linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)" // Green
    // "linear-gradient(135deg, #fa709a 0%, #fee140 100%)" // Sunset
    // "linear-gradient(135deg, #30cfd0 0%, #330867 100%)" // Ocean
  },

  // Old slider config (disabled)
  delay: 5,
  imagePath: [
    "./public/slide/img-1.webp",
    "./public/slide/img-2.webp",
    "./public/slide/img-3.webp",
  ],
};
