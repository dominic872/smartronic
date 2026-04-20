
import re

file_path = '/Users/dominic/Sites/localhost/smartronic/public_html/ro-water-purifier/index.php'

with open(file_path, 'r') as f:
    content = f.read()

# CSS Replacements
replacements = [
    # Styles removal (handled by CSS file now)
    (r'<style>img:is\(\[sizes="auto" i\], \[sizes\^="auto," i\]\) \{ contain-intrinsic-size: 3000px 1500px \}</style>', ''),
    (r'<style>@font-face\{[^}]+\}@font-face\{[^}]+\}@font-face\{[^}]+\}</style>', ''),
    
    # Class replacements
    (r'style="padding-top:10px;padding-bottom:10px"', 'class="nav-padding-fix"'),
    (r'style="font-style:normal;font-weight:400;"', 'class="nav-item-font"'),
    (r'style="font-size:clamp\(14px, 0.875rem \+ \(\(1vw - 3.2px\) \* 0.104\), 15px\);font-style:normal;font-weight:400;"', 'class="nav-item-font nav-item-font-size"'),
    (r'style="font-style:normal;font-weight:700"', 'class="btn-font-style"'),
    (r'style="background-color:#f40009"', 'class="btn-bg-color"'),
    (r'style="border-left-color:var\(--wp--preset--color--accent-4\);border-left-style:dashed;flex-basis:1px"', 'class="column-separator"'),
    (r'style="flex-basis:60%"', 'class="column-flex-60"'),
    (r'style="flex-basis:40%"', 'class="column-flex-40"'),
    (r'style="flex-basis:50%"', 'class="column-flex-50"'),
    (r'style="flex-basis:30%"', 'class="column-flex-30"'),
    (r'style="flex-basis:20%"', 'class="column-flex-20"'),
    (r'style="flex-basis:35%"', 'class="column-flex-35"'),
    
    # Handle display:none carefully to avoid partial replacements
    (r'style="display:none;"', 'class="u-hidden"'), 
    (r'style="display:none"', 'class="u-hidden"'),
    
    (r'style="display:block; text-align: center;"', 'class="risk-display-container"'),
    (r'style="margin-bottom: 5px; font-weight: bold; color: #333;"', 'class="risk-text-margin"'),
    (r'style="margin-bottom: 10px; font-size: 14px; color: #666;"', 'class="risk-subtext-margin"'),
    (r'style="display:block; font-weight:bold; margin-bottom:10px; color:#333;"', 'class="risk-label"'),
    (r'style="margin-top: 5px; font-weight: bold; color: #d9534f;"', 'class="risk-high-text"'),
    (r'style="margin-top: 5px; font-weight: bold; color: #ff9800;"', 'class="risk-medium-text"'),
    (r'style="margin-top: 15px; font-size: 13px; color: #555; line-height: 1.4;"', 'class="risk-description"'),
    (r'style="color: #00c853; margin-right: 5px;"', 'class="icon-success"'),
    (r'style="color: #a00000; margin-right: 5px;"', 'class="icon-error"'),
    (r'style="color:#d9534f;"', 'class="current-choice-cost"'),
    (r'style="display:none; text-align: center; font-size: 20px;"', 'class="brp-header-style"'),
    (r'style="font-size: 16px;"', 'class="brp-rating-style"'),
    (r'style="color: #ff000d"', 'class="brp-star-red"'),
    (r'style="color: #999;"', 'class="brp-star-gray"'),
    (r'style="color: #000000; font-size: 16px;"', 'class="brp-label-style"'),
    (r'style="cursor: pointer; margin-left: 10px;"', 'class="brp-info-icon-style"'),
    (r'style="background-color:#232f3e;padding-top:var\(--wp--preset--spacing--60\);padding-bottom:var\(--wp--preset--spacing--60\)"', 'class="footer-group-style"'),
    (r'style="padding-top:0;padding-bottom:0"', 'class="footer-inner-group"'),
    (r'style="width:336px;height:auto"', 'class="footer-logo-img"'),
    (r'style="font-style:normal;font-weight:300"', 'class="footer-heading-font"'),
    (r'style="display:inline;"', 'class="whatsapp-form"'),
    (r'style="background:none;border:none;padding:0;cursor:pointer;"', 'class="whatsapp-btn"'),
    (r'style="display: none; overflow-y: auto;  overflow-y: none;"', 'class="overlay-hidden"'),
    
    # Remove inline scripts (moved to external JS)
    (r'<script>\s*window\.dataLayer = window\.dataLayer \|\| \[\];[\s\S]*?</script>', ''),
    (r'<script id="wp-block-template-skip-link-js-after">[\s\S]*?</script>', ''),
    
    # Remove Variable scripts (moved to JS)
    (r'<script id="crf-form-script-js-extra">[\s\S]*?</script>', ''),
    (r'<script id="uagb-image-gallery-js-js-extra">[\s\S]*?</script>', ''),
]

for old, new in replacements:
    # Check if 'style=' is in the old string, if so, we need to handle merging with existing class if present?
    # For now, simple replacement. If an element already has a class, the browser handles multiple class attributes poorly (only first counts)
    # or invalid HTML.
    # Better approach: Find tag with this style, and append class to existing class attribute or create one.
    
    # But since I know the file content, most of these elements look like:
    # <div class="..." style="...">
    # So replacing style="..." with class="..." will result in <div class="..." class="..."> which is bad.
    
    if 'style=' in old:
        # Regex to find the style attribute and replace it with class
        # We need to find where this style string occurs, see if there is a class attribute nearby.
        pass

# Re-thinking strategy: Use specific regex for each replacement to handle class merging.
# Example: <div class="wp-block-group ..." style="padding-top:10px...">
# Replace with: <div class="wp-block-group ... nav-padding-fix">

def replace_style_with_class(content, style_str, class_name):
    # Escape style string for regex
    # We look for the exact style string
    # We need to find the HTML tag containing this style.
    # This is complex with regex. 
    # Alternative: simple string replacement of `style="..."` with `class="..."` is dangerous if class already exists.
    
    # However, looking at the file, most elements DO have classes.
    # So `style="..."` should be removed, and the class name appended to the `class="..."` attribute.
    
    # Let's try to match the style string, and then look backwards for `class="`.
    # If found, append. If not found (rare), create class attribute.
    
    # Actually, simpler: 
    # 1. Replace `style="THE_STYLE"` with `data-style-class="THE_CLASS"`
    # 2. Post-process to merge `data-style-class` into `class`.
    
    return content.replace(style_str, f'data-replacement-class="{class_name}"')

# Apply replacements using the temp attribute strategy
for old, new_class in replacements:
    if old.startswith('style='):
        style_content = old.replace('style=', '')
        # Remove quotes from style_content for exact matching if needed, but the list has quotes
        content = content.replace(old, f'data-new-class={new_class.replace("class=", "")}')
    else:
        # Script tag removals
        content = re.sub(old, new, content)

# Now merge data-new-class into class
# Pattern: class="EXISTING" ... data-new-class="NEW"
# or data-new-class="NEW" ... class="EXISTING"
# or just data-new-class="NEW" (create class)

# Case 1: class exists
def merge_classes(match):
    full_tag = match.group(0)
    
    # Extract new class
    new_class_match = re.search(r'data-new-class="([^"]+)"', full_tag)
    if not new_class_match: return full_tag
    new_cls = new_class_match.group(1)
    
    # Extract existing class
    existing_class_match = re.search(r'class="([^"]+)"', full_tag)
    
    if existing_class_match:
        existing_cls = existing_class_match.group(1)
        # Append new class
        updated_cls = f'{existing_cls} {new_cls}'
        # Replace class attribute
        full_tag = full_tag.replace(f'class="{existing_cls}"', f'class="{updated_cls}"')
        # Remove data attribute
        full_tag = re.sub(r'\s*data-new-class="[^"]+"', '', full_tag)
    else:
        # Rename data-new-class to class
        full_tag = full_tag.replace('data-new-class=', 'class=')
        
    return full_tag

# Regex to match tags with data-new-class
# <[^>]*data-new-class="[^"]+"[^>]*>
content = re.sub(r'<[^>]*data-new-class="[^"]+"[^>]*>', merge_classes, content)

with open(file_path, 'w') as f:
    f.write(content)
