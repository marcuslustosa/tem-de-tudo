# Design System Strategy: The Radiant Curator

## 1. Overview & Creative North Star
This design system is built to transform a high-volume commercial PWA into a "Radiant Curator"—a premium digital concierge that feels both energetic and authoritative. We are moving away from the "flat web" aesthetic typical of budget loyalty apps, instead adopting a **Native-Immersive** language. 

The "Creative North Star" is **Dynamic Depth**. By utilizing the vibrant Brazilian color palette not just as accents, but as light sources, we create an interface that feels like it’s glowing from within. We break the rigid mobile grid through intentional overlapping of promotional banners and benefit cards, using the 1.5rem (xl) roundedness scale to soften the high-contrast "commercial" energy into a sophisticated, touch-first experience.

---

## 2. Colors & Surface Logic
The palette is a sophisticated transition from deep, authoritative darks to high-energy magentas. 

### The "No-Line" Rule
**Strict Mandate:** Designers are prohibited from using 1px solid borders to section content. Boundaries must be defined through background color shifts or tonal transitions. To separate a "Partner Card" from the background, use `surface-container-low` (#f2eff9) against the `surface` (#f8f5fe) background. This creates a clean, editorial look that feels "built" rather than "outlined."

### Surface Hierarchy & Nesting
Treat the UI as a series of physical layers.
*   **Base:** `surface` (#f8f5fe) for the overall app canvas.
*   **Level 1 (Sections):** `surface-container` (#e9e7f1) for grouping similar benefit categories.
*   **Level 2 (Active Cards):** `surface-container-lowest` (#ffffff) for individual promotional cards to make them "pop" against the section background.

### The "Glass & Gradient" Rule
To capture the "Vibrant/Commercial" vibe, the signature gradient (`#003B49` → `#7A2C8F` → `#E10098`) must be used for Hero Banners and high-impact CTAs. For floating elements like "Loyalty Badges," use **Glassmorphism**: 
*   **Fill:** `surface_variant` (#dedbe7) at 60% opacity.
*   **Blur:** 12px Backdrop Blur.
*   **Effect:** This allows the vibrant background gradients to bleed through, softening the interface.

---

## 3. Typography: Editorial Authority
We utilize **Poppins** (mapped to our scale) to drive scannability and "Premium Commercial" tone.

*   **Display (Bold):** Use `display-md` (2.75rem) for big "Cashback" numbers or "50% OFF" headlines. This is your "Hook."
*   **Headlines (Bold):** `headline-sm` (1.5rem) for section titles. Use `on_surface` (#2e2e34) to maintain a heavy, authoritative weight.
*   **Titles (SemiBold):** `title-md` (1.125rem) for card headers and benefit names.
*   **Body (Regular):** `body-md` (0.875rem) for benefit descriptions.
*   **Labels (SemiBold):** `label-sm` (0.6875rem) in ALL CAPS for category tags (e.g., "GASTRONOMIA", "SAÚDE").

---

## 4. Elevation & Depth
Depth is achieved through **Tonal Layering**, not shadows.

*   **The Layering Principle:** Avoid "Drop Shadow" presets. Instead, use the spacing scale (`spacing-2`) to create "breathing room" between nested surfaces. A `surface-container-highest` button on a `surface-container` background provides enough contrast to signify interactability.
*   **Ambient Shadows:** If a card must float (e.g., a floating Action Button), use: `box-shadow: 0 8px 32px rgba(11, 31, 58, 0.08)`. Note the use of `Deep Blue` (#0B1F3A) for the shadow tint—never use pure black.
*   **The "Ghost Border" Fallback:** For input fields or secondary buttons, use `outline-variant` (#aeacb4) at **15% opacity**. It should be felt, not seen.

---

## 5. Components

### Promotional Banners
*   **Structure:** Edge-to-edge bleed or `spacing-4` margin with `xl` (1.5rem) corner radius.
*   **Visual:** Use the signature gradient as a background overlay. Text must be `on_primary_fixed` (#000000) or `on_primary` (#ffeff3) depending on the gradient node intensity.

### Benefit & Partner Cards
*   **Style:** No borders. Use `surface-container-lowest` (#ffffff) with an `md` (0.75rem) radius.
*   **Interaction:** On press, transition background to `surface-container-high`.
*   **Hierarchy:** Partner logo (leading), Title/Description (center), Discount Badge (trailing).

### Loyalty Progress Bars
*   **Track:** `surface-container-highest` (#dedbe7).
*   **Indicator:** `primary` (#b00076) to `secondary` (#87399c) horizontal gradient.
*   **Height:** `spacing-2` (0.5rem) with `full` rounding.

### Fixed Bottom Navigation (5 Tabs)
*   **Surface:** `surface-container-lowest` (#ffffff) with a subtle `tertiary` (#00666e) top-glow (20% opacity).
*   **Icons:** Use `on_surface_variant` for inactive and `primary` for active states.
*   **Active Indicator:** A small `spacing-1` dot below the active icon, rather than a background pill.

### Loyalty Badges
*   **Visual:** Circular, using `Accent Cyan` (#00C2D1) for high-tier status or `Medium Purple` (#7A2C8F) for standard.
*   **Effect:** Apply a 10% inner-glow to give a "metallic" or "gem" feel, moving away from flat vector icons.

---

## 6. Do’s and Don’ts

### Do
*   **Do** overlap elements. Let a benefit card peak over a promotional banner by `spacing-4` to create vertical momentum.
*   **Do** use asymmetrical spacing. Use `spacing-8` at the top of a category and `spacing-4` between items to guide the eye.
*   **Do** use the `Accent Cyan` (#00C2D1) sparingly—only for "New" alerts or "Redeem" buttons.

### Don’t
*   **Don’t** use 1px dividers between list items. Use `spacing-3` of vertical white space instead.
*   **Don’t** use sharp corners. Everything in this system must feel "soft-to-the-touch" (minimum `sm` radius for even the smallest chips).
*   **Don’t** use pure grey shadows. Always tint shadows with the `Deep Blue` (#0B1F3A) to keep the "vibrant" brand soul intact.