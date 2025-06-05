# Blaze Builder for Laravel

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-8%2B-red.svg)](https://laravel.com/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://php.net/)

Blaze Builder is a comprehensive, modern asset and build management package for Laravel applications. It streamlines asset compilation, environment configuration, and deployment, supporting both traditional Laravel Mix and the latest Vite.js workflows.

---

## ✨ Key Features

- **Instant Asset Building:** Compile and optimize your assets with a single command.
- **Vite.js & Laravel Mix Support:** Effortlessly switch between asset bundlers.
- **Zero-Hassle Environment Setup:** Smart .env configuration for any environment.
- **Production-Ready:** Optimized for fast, secure, and scalable deployment.
- **Flexible Configuration:** Fully customizable via a published config file.
- **Seamless Integration:** Works out-of-the-box with existing Laravel projects.
- **Detailed Logging:** Transparent build output for easy troubleshooting.
- **Automatic Asset Versioning:** Cache-busting for static assets.
- **Public Path Management:** Simple handling of asset URLs, even on custom domains.
- **Developer Friendly:** Clear documentation, copy-paste commands, and best practices.
- **Encrypted App Builds:** Generate encrypted builds so you can share your source without risk—no one can change your source code.
- **Device-Specific Builds:** Optionally lock builds to a specific device. These builds will only run on the selected device and nowhere else.
- **Interactive Build Lock:** During build, you'll see a prompt: "Do you want to lock this build to this device only?" If you answer "yes", the build will be restricted to the current device.
- **Open Source:** MIT licensed and welcomes community contributions.

---

## 📦 Installation

Install Blaze Builder using Composer:

```bash
composer require laravel-blaze/builder
```

Publish the Blaze configuration file:

```bash
php artisan vendor:publish --tag=blaze-config --force
```

---

## 🖥️ Requirements

- **PHP:** 7.4 or higher
- **Laravel:** 8.x, 9.x, or newer
- **Composer:** Latest recommended
- **Node.js & NPM:** Required for Vite.js or Laravel Mix workflows

---

## ⚙️ Environment Configuration

Add the following to your `.env` file:

```dotenv
APP_ENV=production
APP_DEBUG=false
```

**If using Vite.js, add:**
```dotenv
APP_URL=https://example.com
ASSET_URL=https://example.com/public
```
> Replace `example.com` with your actual domain.

---

## 🏗️ Build Process

Compile and optimize your assets for production:

```bash
php artisan build
```

- This will handle asset bundling via your configured builder (Vite.js or Laravel Mix).
- Output and errors are logged for auditing and debugging.
- **Interactive Device Lock:** During this process, you'll be asked:  
  _"Do you want to lock this build to this device only?"_  
  If you choose "yes", the build will only work on this device.
- **Encrypted Builds:** You can choose to encrypt your app so your source can be shared, but no one can change your source code.

---

## 🔥 Supported Workflows

### Laravel Mix

- Default for Laravel <= 9.x
- Supports SASS, LESS, JS, Vue, React, and more

### Vite.js

- Default for Laravel >= 9.x
- Lightning-fast HMR and modern JS ecosystem

**Switch workflows in your Blaze config (`blaze.json`) as needed.**

---

## 🛠️ Blaze Configuration

After publishing, adjust the config in:

```
blaze.json
```

- Set asset builder (Mix/Vite)
- Define public paths, asset roots, versioning, and more
- Enable/disable logging
- Customize build scripts
- **Enable Encryption:** Lock your app code from modification
- **Set Device Lock:** Restrict build to specific device

---

## 💡 Tips & Best Practices

- Always set correct `APP_URL` and `ASSET_URL` for asset links and CORS.
- Use `APP_ENV=production` and `APP_DEBUG=false` for best security and performance.
- Rebuild assets after updating dependencies or changing config.
- Check log output if build fails.
- Use encryption and device-lock features for extra security when distributing your app.

---

## 📋 Copy-Paste Commands

```bash
# Install Blaze Builder
composer require laravel-blaze/builder

# Publish config file
php artisan vendor:publish --tag=blaze-config --force

# Build assets
php artisan build
```

---

## 🧩 Integration Examples

### Example: Vite.js with Custom Domain

```dotenv
APP_ENV=production
APP_URL=https://mydomain.com
ASSET_URL=https://cdn.mydomain.com/public
```

### Example: Updating Build Script

Edit `blaze.json` as needed.

---

## 🚦 Troubleshooting

- **Build fails?** Check the output logs for detailed errors.
- **Assets missing?** Confirm `ASSET_URL` and public path in config match your deployment.
- **Environment not detected?** Ensure `.env` is up-to-date and permissions are correct.
- **Encrypted or device-locked build fails elsewhere?** Make sure you're running on the correct device, and the build hasn't been tampered with.

---

## 🔒 Security Features

### Encrypted App Builds

- Share your source code securely—no one can change your code after build.
- Useful for distributing apps to clients or partners while protecting your IP.

### Device-Specific Builds

- Lock builds to a specific device (e.g., server, VM, or developer machine).
- Prevents unauthorized copying or running your app elsewhere.
- Interactive prompt ensures you don't accidentally lock a build.

---

## 📝 License

Blaze Builder is open-source software licensed under the [MIT license](LICENSE).

---

## 🤝 Contributing

Pull requests and issues are welcome!  
See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

---

## 📢 Support

For questions, suggestions, or bugs, open an [issue](https://github.com/engineertuhin/Blaze/issues).

---

**Build smarter, deploy faster — with Blaze Builder! 🚀**