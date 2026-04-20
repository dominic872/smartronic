
import re

file_path = '/Users/dominic/Sites/localhost/smartronic/public_html/ro-water-purifier/index.php'

with open(file_path, 'r') as f:
    content = f.read()

replacements = [
    (r'style="font-size:clamp\(14px, 0.875rem \+ \(\(1vw - 3.2px\) \* 0.104\), 15px\);font-style:normal;font-weight:400;"', 'class="nav-font-clamp"'),
    (r'style="border-left-color:var\(--wp--preset--color--accent-4\);border-left-style:dashed;flex-basis:1px"', 'class="column-separator"'),
    (r'style="background-color:rgba\(0, 0, 0, 0\)"', 'class="mark-bg-transparent"'),
    # Fixed regex: removed backslash before single quotes
    (r'style="--uagb-bg: url\(\'ro-water-purifier/image/family.png\'\);"', ''), 
    (r'style="border-radius:10px"', 'class="border-radius-10"'),
    (r'style="flex-basis:65%"', 'class="column-flex-65"'),
    (r'style="background-color:#232f3e;padding-top:var\(--wp--preset--spacing--60\);padding-bottom:var\(--wp--preset--spacing--60\)"', 'class="footer-group-style"'),
    (r'style="color: #00c676; margin-right: 5px;"', 'class="icon-check-green"'),
    (r'style="margin-top: 40px;"', 'class="mt-40"'),
    (r'style="display: flex; flex-direction: column; gap: 5px;"', 'class="flex-col-gap-5"'),
    (r'style="display: flex; align-items: center; gap: 10px;"', 'class="flex-row-center-gap-10"'),
]

# Apply replacements
for old, new_val in replacements:
    if new_val == '':
        content = re.sub(old, '', content)
    else:
        new_class = new_val.replace('class=', '').replace('"', '')
        content = re.sub(old, f'data-new-class="{new_class}"', content)

def merge_classes(match):
    full_tag = match.group(0)
    new_class_match = re.search(r'data-new-class="([^"]+)"', full_tag)
    if not new_class_match: return full_tag
    new_cls = new_class_match.group(1)
    
    existing_class_match = re.search(r'class="([^"]+)"', full_tag)
    if existing_class_match:
        existing_cls = existing_class_match.group(1)
        updated_cls = f'{existing_cls} {new_cls}'
        full_tag = full_tag.replace(f'class="{existing_cls}"', f'class="{updated_cls}"')
        full_tag = re.sub(r'\s*data-new-class="[^"]+"', '', full_tag)
    else:
        full_tag = full_tag.replace('data-new-class=', 'class=')
    return full_tag

content = re.sub(r'<[^>]*data-new-class="[^"]+"[^>]*>', merge_classes, content)

with open(file_path, 'w') as f:
    f.write(content)
