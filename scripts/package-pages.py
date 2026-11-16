"""Build an allowlisted content-only deployment bundle. No plugins, uploads or SEO."""
import json
from pathlib import Path
import re
import sys
import tarfile

root = Path(__file__).resolve().parent.parent
manifest = json.loads((root / "content/pages-staging.json").read_text(encoding="utf-8-sig"))
files = {
    "content/pages-staging.json",
    "scripts/pages.php",
    "scripts/lib/page-content.php",
    "scripts/lib/page-deployer.php",
    "scripts/import-staging-home.php",
    "scripts/remote-pages.sh",
}
for page in manifest["pages"]:
    name = page["file"]
    if not re.fullmatch(r"content/[a-zA-Z0-9_./-]+\.json", name) or ".." in name:
        raise SystemExit("Unsafe content path")
    if name.endswith("-seo.json"):
        raise SystemExit("SEO files cannot be packaged")
    path = root / name
    if path.is_symlink() or not path.resolve().is_relative_to((root / "content").resolve()):
        raise SystemExit("Content escapes the allowlist")
    files.add(name)
# The compatibility check always needs the homepage file, even when another page is selected.
files.add("content/home-staging.json")
with tarfile.open(sys.argv[1], "w:gz") as archive:
    for name in sorted(files):
        path = root / name
        if not path.is_file() or path.is_symlink():
            raise SystemExit("Missing or symlinked deployment file: " + name)
        archive.add(path, arcname=name, recursive=False)
print("Packaged", len(files), "allowlisted files")
