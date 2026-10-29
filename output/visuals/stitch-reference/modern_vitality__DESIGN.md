---
name: Modern Vitality
colors:
  surface: '#f8f9ff'
  surface-dim: '#cbdbf5'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e5eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d3e4fe'
  on-surface: '#0b1c30'
  on-surface-variant: '#3e4947'
  inverse-surface: '#213145'
  inverse-on-surface: '#eaf1ff'
  outline: '#6e7977'
  outline-variant: '#bdc9c6'
  surface-tint: '#006a63'
  primary: '#005c55'
  on-primary: '#ffffff'
  primary-container: '#0f766e'
  on-primary-container: '#a3faef'
  inverse-primary: '#80d5cb'
  secondary: '#855300'
  on-secondary: '#ffffff'
  secondary-container: '#fea619'
  on-secondary-container: '#684000'
  tertiary: '#3b3bc9'
  on-tertiary: '#ffffff'
  tertiary-container: '#5457e2'
  on-tertiary-container: '#eae8ff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#9cf2e8'
  primary-fixed-dim: '#80d5cb'
  on-primary-fixed: '#00201d'
  on-primary-fixed-variant: '#00504a'
  secondary-fixed: '#ffddb8'
  secondary-fixed-dim: '#ffb95f'
  on-secondary-fixed: '#2a1700'
  on-secondary-fixed-variant: '#653e00'
  tertiary-fixed: '#e1e0ff'
  tertiary-fixed-dim: '#c0c1ff'
  on-tertiary-fixed: '#07006c'
  on-tertiary-fixed-variant: '#2f2ebe'
  background: '#f8f9ff'
  on-background: '#0b1c30'
  surface-variant: '#d3e4fe'
typography:
  display:
    fontFamily: Plus Jakarta Sans
    fontSize: 48px
    fontWeight: '800'
    lineHeight: 56px
    letterSpacing: -0.02em
  display-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 34px
    fontWeight: '800'
    lineHeight: 42px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.015em
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 26px
    fontWeight: '700'
    lineHeight: 34px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  title-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 15px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 20px
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.01em
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 11px
    fontWeight: '700'
    lineHeight: 16px
    letterSpacing: 0.04em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1.5rem
  gutter-mobile: 1rem
  margin: 2rem
  margin-mobile: 1rem
  margin-desktop: 3rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2.5rem
---

## Brand & Style

This design system establishes a clinical-grade yet deeply human visual sanctuary. Balancing scientific credibility with organic warmth, the aesthetic pairs crisp architectural structure with soothing fluid surfaces. It serves enterprise employees tracking preventative health and individual practitioners cultivating mindfulness, physical vitality, and emotional balance.

The visual style merges **Warm Minimalism** with **Subtle Glassmorphic Depth**:
- **Clarity & Serenity:** Expansive breathable whitespace, precise content zoning, and intentional typographic rhythm reduce cognitive fatigue.
- **Empathetic Tactility:** Soft, organic curvatures (pill-shaped interactive elements and generously rounded containers) remove clinical austerity while preserving professional authority.
- **Translucent Depth:** Grounded slate foundations with frosted teal-tinted glassmorphic overlays provide layered context without clutter.
- **Motivational Energy:** Purposeful splashes of warm vitality amber and restorative lavender inject positive reinforcement into habit loops, milestone streaks, and biometric insights.

## Colors

The palette balances restorative botanical tones with invigorating vital accents and neutral slate backbones, meticulously engineered for WCAG AA/AAA compliance across light and dark contexts.

### Palette Roles
- **Primary (Sage Teal - `#0F766E`, `#14B8A6`):** Grounded restoration and balance. Represents physiological equilibrium, primary action triggers, and verified health milestones.
- **Secondary (Vitality Amber/Coral - `#F59E0B`, `#F97316`):** Metabolic energy and celebration. Governs streak mechanics, progress velocity, active timer states, and achievement badges.
- **Tertiary (Serene Indigo/Lavender - `#6366F1`, `#818CF8`):** Cognitive restoration and psychological safety. Reserved for mental health logging, sleep analytics, meditation flows, and circadian insights.
- **Neutrals (Slate - `#0F172A` to `#F8FAFC`):** Balanced, high-legibility typographic scale and ambient containers with zero muddy desaturation.

### Dark Mode Semantic Mapping
- **Light Canvas:** Base surface `#F8FAFC`, card surface `#FFFFFF`, subtle borders `#E2E8F0`, deep slate text `#0F172A`.
- **Dark Canvas:** Base surface `#0B0F19`, card surface `#131C2E`, elevated card `#1E293B`, subtle borders `rgba(255, 255, 255, 0.08)`, soft slate text `#F1F5F9`.
- **Status Accents:** In dark mode, primary and secondary tokens step down by one shade stop (`#14B8A6` and `#FBBF24`) to eliminate visual glare while maintaining minimum 4.5:1 contrast against dark containers.

## Typography

The typographic architecture combines **Plus Jakarta Sans** for expressive, humanistic headers and metrics with **Inter** for dense, clinical data feeds and body text.

- **Plus Jakarta Sans:** Rounded apertures, friendly yet confident geometry, and distinct character terminals bring empathy and approachability to titles, biometric stat readouts, and gamification tiers.
- **Inter:** Exceptional x-height, neutral micro-grotesque structure, and tabular numerical rendering (`tnum`) deliver effortless scanability across heart-rate variability charts, sleep staging breakdowns, and enterprise reporting tables.
- **Numerical Treatment:** Biometric callouts, streak counters, and vital signs utilize tabular figures with negative tracking to ensure visual stability during live real-time transitions.

## Layout & Spacing

The layout is built on a responsive 12-column grid for desktop/tablet transitions and an adaptive 4-column structure for mobile viewports, anchored by an 8pt foundational spacing rhythm.

### Grid & Breakpoints
- **Mobile (< 768px):** 4 columns, `1rem` outer margins, `1rem` gutters. Content cascades vertically; telemetry feeds collapse into swipeable horizontal rails with trailing edge peeks (showing `16px` of the next card).
- **Tablet (768px - 1024px):** 8 columns, `2rem` outer margins, `1.5rem` gutters. Split screen layouts (e.g., mindfulness session timer alongside vital status).
- **Desktop (> 1024px):** 12 columns, max-width `1360px`, `3rem` outer margins, `1.5rem` gutters. Accommodates persistent left-hand navigation, central dynamic feeds, and dedicated contextual telemetry panels on the right.

### Spacing Principles
Internal component padding relies strictly on semantic scales:
- Micro actions (badges, metric pills): `space-xs` (4px) to `space-sm` (8px).
- Interactive cards, form controls: `space-md` (16px) to `space-lg` (24px).
- Structural section separations and wellness domain milestones: `space-xl` (40px).

## Elevation & Depth

Visual depth is achieved through an ambient layered ecosystem, eliminating harsh drop shadows in favor of biological, soft-light dispersion and translucent membranes.

### Depth Hierarchy
1. **Canvas Surface (Level 0):** Pure restorative foundation (`#F8FAFC` light / `#0B0F19` dark) with optional subtle radial ambient gradients of soft sage and lavender at 3% opacity.
2. **Standard Elevated Cards (Level 1):** Solid cards (`#FFFFFF` light / `#131C2E` dark) resting over low-intensity diffused shadows: `0px 4px 20px -2px rgba(15, 23, 42, 0.05)`. Encased in an ultra-subtle 1px border (`rgba(226, 232, 240, 0.8)` light / `rgba(255, 255, 255, 0.06)` dark).
3. **Glassmorphic Interactive Surfaces (Level 2):** Floating player trays, modal overlays, and sticky quick-action bars:
   - Light: `rgba(255, 255, 255, 0.82)` with `backdrop-filter: blur(16px) saturate(160%)` and inner top border `rgba(255, 255, 255, 0.6)`.
   - Dark: `rgba(19, 28, 46, 0.78)` with `backdrop-filter: blur(16px) saturate(140%)` and inner top border `rgba(255, 255, 255, 0.12)`.
   - Shadow: `0px 12px 32px -4px rgba(15, 23, 42, 0.12)`.
4. **Gamification & Hero Flares (Level 3):** Active streaks and breakthrough milestones carry a secondary tinted ambient glow: `0px 8px 24px -2px rgba(245, 158, 11, 0.25)`.

## Shapes

The shape hierarchy is organic and deeply friendly, balancing fluid wellness cues with technical order:

- **Base Elements (`rounded-md` / 0.5rem - 8px):** Checkboxes, nested metric tags, inner status indicators.
- **Interactive Controls (`rounded-lg` / 1rem - 16px):** Form fields, standard primary/secondary buttons, segmented controls.
- **Card Enclosures (`rounded-xl` / 1.5rem - 24px):** Biometric widgets, habit trackers, content feeds, dashboard panels.
- **Pill System (`rounded-full` / 9999px):** Status chips, gamification milestone badges, streak pills, and mobile floating action triggers.

## Components

### Buttons
- **Primary Action:** Solid Sage Teal (`#0F766E`), text `#FFFFFF`, `rounded-lg` (16px), height 48px, horizontal padding `space-lg`. Hover brings subtle brightness shift and `0px 4px 14px rgba(15, 118, 110, 0.35)`.
- **Secondary / Soft:** Subtle Sage Tint (`#CCFBF1` light / `rgba(20, 184, 166, 0.15)` dark), text `#0F766E` (light) / `#2DD4BF` (dark). Flat elevation.
- **Ghost:** Text Slate (`#475569`), hover background `rgba(15, 23, 42, 0.04)`, `rounded-lg`.

### Gamification Chips & Badges
- **Streak Badges:** Pill-shaped (`rounded-full`), padding `4px 12px`. Light mode: `#FEF3C7` background, `#D97706` text and flame icon. Dark mode: `rgba(245, 158, 11, 0.15)` background, `#FBBF24` text.
- **Level / Tier Indicators:** Pill-shaped, semi-translucent Lavender gradient (`linear-gradient(135deg, #6366F1, #8B5CF6)`), text `#FFFFFF`, accompanied by numerical tracking in `label-sm`.
- **Status Indicators:** Micro dot (6px) + text label inside a subtle outline pill (`border: 1px solid rgba(100, 116, 139, 0.2)`). Green for synchronized vitals, amber for target pending, indigo for active meditation.

### Cards & Habit Trackers
- **Daily Habit Tile:** Level 1 elevation, `rounded-xl` (24px), padding `space-lg`. Contains icon container (`rounded-lg`, 40x40px with soft category color), title in `title-md`, streak chip in top right, and progressive ring/bar at the bottom.
- **Biometric Summary Card:** Glassmorphic Level 2 depth on hover, displaying large value in `headline-lg` (Plus Jakarta Sans) and unit in `body-sm` (Inter muted slate).

### Form Inputs & Selectors
- **Input Fields:** Height 48px, background `#FFFFFF` (light) / `#0F172A` (dark), border 1px solid `#CBD5E1` (light) / `#334155` (dark), `rounded-lg` (16px), inner padding `0 16px`. Focus state triggers teal ring: `box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.25)`, border color `#0F766E`.
- **Checkboxes & Radios:** Size 20x20px, `rounded-md` (6px for check, full pill for radio), teal fill with smooth scale transition upon selection.

### Progress & Telemetry Bars
- Track: Height 8px, background `#E2E8F0` (light) / `#1E293B` (dark), `rounded-full`.
- Indicator: Dual-tone linear gradient matching the domain (Vitality: `#F59E0B` to `#F97316`; Rest: `#6366F1` to `#818CF8`; Baseline Health: `#0F766E` to `#14B8A6`), `rounded-full`.