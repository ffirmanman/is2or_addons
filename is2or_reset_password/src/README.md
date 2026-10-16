# IS2OR Reset Password Addon
**CS-Cart Multivendor 4.19+**

## Deskripsi

Addon ini mengubah perilaku link reset password yang dikirim via email.

**Sebelum addon:**
1. User klik "Forgot Password" → input email → terima email
2. User klik link di email → **langsung login & diarahkan ke /profiles-update/**

**Sesudah addon:**
1. User klik "Forgot Password" → input email → terima email
2. User klik link di email → **diarahkan ke halaman Reset Password khusus**
3. User isi form "New Password" + "Re-enter New Password" → klik Save
4. User **diarahkan ke halaman Login** (tidak otomatis login)
5. User login manual dengan password baru

---

## Instalasi

### Cara 1: Upload via Admin Panel
1. Buka **Admin Panel → Add-ons → Manage Add-ons**
2. Klik tombol **"+"** (Upload & Install Add-on)
3. Upload file `is2or_reset_password.zip`
4. Klik **Install**

### Cara 2: Manual
1. Extract `is2or_reset_password.zip`
2. Copy folder `app/addons/is2or_reset_password/` ke direktori CS-Cart kamu
3. Copy folder `design/themes/responsive/templates/addons/is2or_reset_password/` ke direktori CS-Cart
4. Copy folder `var/langs/` ke direktori CS-Cart
5. Buka Admin Panel → Add-ons → Manage Add-ons → cari **IS2OR Reset Password** → klik **Install**

---

## Struktur File

```
app/addons/is2or_reset_password/
├── addon.xml                                   ← Manifest addon
├── func.php                                    ← Helper function validasi ekey
├── controllers/
│   ├── common/
│   │   └── auth.php                            ← Override handler ekey recovery
│   └── frontend/
│       └── is2or_reset_password.php            ← Controller halaman reset password

design/themes/responsive/templates/addons/is2or_reset_password/
└── views/
    └── is2or_reset_password/
        └── reset.tpl                           ← Template form reset password

var/langs/
├── en/addons/is2or_reset_password.po           ← Bahasa Inggris
└── id/addons/is2or_reset_password.po           ← Bahasa Indonesia
```

---

## Cara Kerja (Teknis)

### Flow Baru

```
[Email Link] ?dispatch=auth.recover_password&ekey=XXX
      ↓
[controllers/common/auth.php] (override)
      ↓ validasi ekey via fn_is2or_validate_recovery_ekey()
      ↓ TIDAK login user
      ↓ simpan {user_id, token} ke session['is2or_reset']
      ↓
[Redirect] → is2or_reset_password.reset
      ↓
[controllers/frontend/is2or_reset_password.php] (GET)
      ↓ cek session is2or_reset valid
      ↓
[Template] reset.tpl → form password1 + password2
      ↓ (POST submit)
[controllers/frontend/is2or_reset_password.php] (POST)
      ↓ validasi password match
      ↓ fn_update_user() → update password
      ↓ hapus session is2or_reset
      ↓ logout jika ada session aktif
      ↓
[Redirect] → auth.login_form
```

### Keamanan
- **Ekey hanya bisa dipakai 1x** – langsung dihapus dari DB setelah divalidasi
- **Token session** dibuat dari `md5(ekey + user_id + secret_key)` – tidak bisa dipalsukan
- **User tidak pernah login otomatis** – session hanya menyimpan intent reset
- **Session dihapus** setelah password berhasil diganti

---

## Catatan

- Template menggunakan `views/profiles/components/passwords.tpl` yang sudah ada di CS-Cart (komponen form password bawaan), sehingga validasi JS client-side tetap berjalan.
- Jika theme yang digunakan bukan `responsive`, copy template ke folder theme yang sesuai.
- Addon tidak mengubah core files CS-Cart sama sekali (addon-safe).
