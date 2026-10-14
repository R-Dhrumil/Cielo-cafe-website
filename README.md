# Cielo Cafe and Restro — Lightweight Production PHP Website

A lightweight, high-performance, editorial cafe and restaurant website built in **PHP** for **Cielo Cafe and Restro** (Vadodara, Gujarat). Inspired by Italian trattoria elegance, third-wave specialty cafes, and contemporary all-day restro culture.

---

## 🎨 Theme & Strict Design System

The application strictly maintains the curated, high-contrast editorial cafe palette across all pages:

- **Cream / Beige (`#F6EFE3`)**: Primary backdrop, editorial warmth
- **Deep Espresso (`#1B1511`)**: Primary typography, evening bands, dark footer
- **Sky Blue Signature (`#8FB8D8` / deeper `#3F6E96`)**: "Cielo" (Sky) brand accents, eyebrows, links
- **Warm Terracotta (`#B5623B`)**: Call-to-action buttons, high-priority highlights
- **Off-White Card Surface (`#FBF8F2`)**: Dish cards, review tiles, modal dialogs

### Typography
- **Serif / Display**: `Cormorant Garamond` (Editorial Italian Serif)
- **Sans-Serif / Body**: `Plus Jakarta Sans` (Clean, high-legibility UI)
- **Brand Inscriptional**: `Cinzel` (Classical Roman brand mark)

---

## 📁 Production-Ready Folder Structure

The project has been modularized and separated for production readiness. Every section and page has its own dedicated, separated CSS file for rapid maintenance, inspection, and updates.

```
cielo-cafe/
├── assets/
│   ├── css/
│   │   ├── base.css              # Core theme tokens, typography, shared buttons (.btn-primary, .btn-terracotta, .btn-outline)
│   │   ├── header.css            # Sticky navbar, announcement topbar, mobile drawer
│   │   ├── footer.css            # Footer layout, brand badge, social & delivery links
│   │   ├── home.css              # Hero video background, controls, marquee, split story & ingredient sections
│   │   ├── menu.css              # Signature menu category tabs, dish card grid, PDF download slot
│   │   ├── about.css             # Our story, craftsmanship pillars, brand philosophy
│   │   ├── events.css            # Private bookings, layered composition, event inquiry
│   │   ├── gallery.css           # Visual diary grid, hover zoom overlay, category tags
│   │   ├── reviews.css           # Diner reviews grid, 4.8 Google rating stars, diner badges
│   │   ├── contact.css           # Location info, weekly hours table, embedded responsive Google Map
│   │   ├── newsletter.css        # The Cielo Club VIP signup banner, input & feedback
│   │   └── modal.css             # Table reservation dialog, lightbox modal, accessibility tool, WhatsApp FAB
│   ├── js/
│   │   ├── main.js               # Sticky scroll transition, dynamic live open/closed status clock, mobile drawer
│   │   ├── hero-video.js         # Video controls (audio toggle, play/pause, clear video view)
│   │   ├── menu-tabs.js          # Interactive category switcher & dish filtering
│   │   ├── lightbox.js           # Full-screen photo lightbox with keyboard navigation
│   │   ├── reservation.js        # Table booking modal & instant WhatsApp message routing
│   │   ├── accessibility.js      # Text scaling, high contrast toggle, readable standard font
│   │   └── newsletter.js         # Client-side subscription validation & confirmation
│   ├── images/
│   │   ├── cafe/                 # Ambiance, interior, table spread photos
│   │   └── dishes/               # Artisan food, pizza, espresso & coolers photos
│   └── videos/
│       └── hero-bg.mp4           # High-clarity hero background video
├── data/
│   ├── business.json             # Phone, WhatsApp, address, coordinates, hours schedule, links
│   ├── menu.json                 # Categorized menu with ₹ prices, veg indicators, tags, descriptions
│   ├── reviews.json              # Google diner reviews, star ratings, reviewer badges
│   └── gallery.json              # Visual diary photo metadata, titles, categories, captions
├── includes/
│   ├── config.php                # JSON data loader, timezone (IST), asset helpers, live hours calculation
│   ├── header.php                # HTML5 head, Schema.org Restaurant JSON-LD, topbar, sticky navbar
│   ├── footer.php                # Global footer, social badges, sub-footer, script imports
│   ├── reservation-modal.php     # Interactive reservation popup component
│   ├── lightbox-modal.php        # Lightbox image preview component
│   ├── accessibility-widget.php  # Accessibility toolbar component
│   └── whatsapp-fab.php          # Floating WhatsApp action button component
├── index.php                     # Complete rich landing page with all interactive sections
├── menu.php                      # Dedicated full menu page
├── about.php                     # Dedicated our story & philosophy page
├── events.php                    # Dedicated private events & celebrations page
├── gallery.php                   # Dedicated visual diary & Instagram gallery page
└── contact.php                   # Dedicated contact, hours schedule & map page
```

---

## 🚀 How to Run Locally

You only need standard PHP (PHP 7.4+ or PHP 8.x):

```bash
# Start PHP's built-in development web server:
php -S localhost:8000

# Open in your browser:
http://localhost:8000
```

To run on an Apache or Nginx server (like XAMPP, MAMP, or standard cPanel/VPS), simply point the virtual host or `DocumentRoot` to this folder.

---

## ✨ Features Included

1. **Lightweight & Ultra-Fast**: Zero heavy node runtime or bundler required in production. Instant page loads.
2. **Real-time Live Operating Status**: Dynamic calculation of whether Cielo is currently open (11:00 AM – 11:30 PM IST) with pulsating live status badge in the top bar.
3. **Hero Video with Controls**: Interactive bottom-left controls for sound (mute/unmute), playback (play/pause), and a "Clear Video View" toggle to admire the ambiance.
4. **Interactive Menu Filter**: Instant category switching (Coffee, Starters, Mains, Pizza & Pasta, Desserts) with veg badges and instant WhatsApp pre-filled ordering.
5. **Table Reservation Flow**: Modal with date, time, guest count, and occasion selection that directly formats a booking request into WhatsApp.
6. **Full-Screen Lightbox**: Responsive photo gallery with keyboard navigation (Arrows & Escape).
7. **Accessibility Toolbar**: Text scaling (85% - 130%), high-contrast mode, standard font mode, and instant reset.
8. **SEO & Structured Data**: Native Schema.org `Restaurant` & `CafeOrCoffeeShop` JSON-LD, OpenGraph, and Twitter Cards.
