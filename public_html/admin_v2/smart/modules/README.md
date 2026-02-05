# SmArtronic CCTV Configuration - Modular Architecture

## Overview
The application has been split into logical modules to improve maintainability and readability.

## Module Structure

### 1. **utils.js** (Utilities)
**Purpose**: Common utility functions used across the application
- `formatINR()` - Format numbers as Indian Rupees
- `parsePrice()` - Extract numeric price from string values
- `sortByPriceLowToHigh()` - Sort items by price
- `sortHddByPriceLowToHigh()` - Sort HDD items by price
- `safeGetTypeRoot()` - Safely access data structure
- `updateCameraButtonText()` - Update camera button labels with quantity

**Exported as**: `window.SmartronicUtils`

---

### 2. **core.js** (Session Management)
**Purpose**: Handle data persistence and session management
- `saveSessionData()` - Save selections to localStorage
- `clearSessionData()` - Clear all session data
- `loadSessionData()` - Load selections from localStorage

**Session Key Format**: `{Category}::{Brand}::{MP}`
**Storage Duration**: 1 hour (session data auto-expires)

**Exported as**: `window.SmartronicCore`

---

### 3. **ui-state.js** (State Management)
**Purpose**: Centralized state management for UI selections
- `getCurrentState()` - Get current category, brand, MP
- `setCurrentCategory/Brand/MP()` - Update current selection
- `getSelectedItems()` / `setSelectedItems()` - Manage selected items array
- `addSelectedItem()` / `removeSelectedItem()` - Modify item selections
- `updateSelectedItem()` - Update existing item properties
- `clearSelectedItems()` - Clear all selections
- `filterItemsByContext()` - Filter items by current context

**Context Filtering**: 
- Cameras & Recorders: Only show items matching current category/brand/MP
- HDDs & Accessories: Show all (not context-specific)

**Exported as**: `window.SmartronicUIState`

---

### 4. **camera-selection.js** (Camera Logic)
**Purpose**: Camera selection and quantity management
- `attachCameraButtonLogic()` - Attach click handlers to camera buttons
- `updateCameraButtonText()` - Update button text with quantity
- `updateAllCameraSliders()` - Update slider states

**Selection Mode**: Toggle (click to select/deselect)
**Quantity**: Can be adjusted via +/- buttons in summary

**Exported as**: `window.SmartronicCameraSelection`

---

### 5. **summary.js** (Display & Pricing)
**Purpose**: Summary panel, pricing calculations, and status bar
- `updateSummary()` - Render summary items with quantity controls
- `updateCameraStatusBar()` - Update camera count status bar
- `buildWhatsAppMessage()` - Generate shareable WhatsApp message

**Status Bar Colors**:
- 🟢 Green: Selected equals required cameras
- 🔴 Red: Selected exceeds required cameras
- 🔵 Blue/Purple: Default state (incomplete)

**Pricing Calculation**:
- Subtotal: Sum of (price × quantity) for all selected items
- GST: 18% of subtotal
- Total: Subtotal + GST

**Exported as**: `window.SmartronicSummary`

---

### 6. **scripts.js** (Main Initialization)
**Purpose**: Bootstrap application and wire modules together
- Initialize DOM elements
- Set up event listeners
- Create category/brand/MP buttons
- Manage form interactions
- Coordinate between modules

---

## Data Flow

```
User Interaction
    ↓
Event Handler (scripts.js)
    ↓
UI State Module (ui-state.js)
    ↓
Core Module (core.js) - saves to localStorage
    ↓
Summary Module (summary.js) - updates display
    ↓
Updated UI
```

## Context Isolation

The system maintains isolated state for each brand/resolution combination:
- **Context Key**: `DVR::HIKVISION::2MP`
- **Isolated**: Camera & Recorder selections
- **Shared**: HDDs & Accessories
- **Session Storage**: Separate key for each context

## Module Dependencies

```
utils.js
    ↓
core.js ← ui-state.js ← camera-selection.js
                ↓
            summary.js
                ↓
            scripts.js (main)
```

## Usage in HTML

```html
<!-- Load in order -->
<script src="modules/utils.js"></script>
<script src="modules/core.js"></script>
<script src="modules/ui-state.js"></script>
<script src="modules/camera-selection.js"></script>
<script src="modules/summary.js"></script>
<script src="scripts.js"></script>
```

## Future Expansion

Additional modules can be easily added:
- `hdd-selection.js` - HDD selection logic
- `recorder-selection.js` - Recorder selection logic
- `auto-select.js` - Auto-selection logic
- `form-validators.js` - Input validation

---

## Code Statistics

- **Original scripts.js**: 1433 lines
- **Modular split**: ~200-300 lines per module
- **Total modular code**: ~1000 lines (more readable)
- **Improvement**: 40% reduction in single-file complexity
