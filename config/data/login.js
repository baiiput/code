var cfgLogin = {
  default: 1,
  option: 1,

  voucher: {
    value: "default",
    _comment: "default / upper / lower",
  },

  member: {
    value: "default",
    _comment: "default / upper / lower",
  },

  scan_qrcode: {
    status: 0,
    url: "https://eriiksanjaya.github.io/qr",
  },

  input_field: {
    hidden_label: {
      // 1 = sembunyikan label input
      status: 0,
    },

    hidden_input: {
      // 1 = sembunyikan input
      status: 0,
    },

    input_value: {
      // biasanya di rumah sakit, hanya tombol login saja
      // tidak ada masukkan voucher / username password
      // maka isi di sini
      username: "",
      password: "",
    },
  },

  label: {
    title: "Login",
    sub_title: "for free Wi-Fi access",

    option_login: {
      voucher: "Voucher",
      member: "Member",
      trial: "Trial",
      scan: "Scan",
    },

    button: {
      login: "Login",
      logout: "Logout",
    },

    form: {
      voucher_label: "Masukkan Voucher",
      voucher_placeholder: "Input Voucher",

      member_label: "Room Number",
      member_placeholder: "input room number",

      password_label: "Password",
      password_placeholder: "input password",
    },

    status: {
      login: "Info Login",
      logout: "Info Logout",
    },
  },
};
