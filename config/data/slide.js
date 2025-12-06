var cfgSlide = {
  status: 0,  // 0 = slider disabled, use static background instead

  // Static background configuration
  staticBackground: {
    enabled: 1,  // 1 = use static background

    // Use image background
    imagePath: "./public/slide/img-1.webp",

    // Optional: Use gradient instead (set useGradient: 1)
    useGradient: 0,
    gradient: "linear-gradient(135deg, #667eea 0%, #764ba2 100%)",
  },

  // Old slider config (disabled)
  delay: 5,
  imagePath: [
    "./public/slide/img-1.webp",
    "./public/slide/img-2.webp",
    "./public/slide/img-3.webp",
  ],
};
