#!/usr/bin/env python3
from __future__ import annotations

import re
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
TEMPLATE = (
    ROOT
    / "requirements"
    / "docs_temlpates"
    / "doc"
    / "Договор_оказания_юридических_услуг_ООО__Линки__template.docx"
)


def main() -> None:
    with zipfile.ZipFile(TEMPLATE) as archive:
        for part in ("word/header1.xml", "word/document.xml"):
            xml = archive.read(part).decode("utf-8")
            placeholders = sorted(set(re.findall(r"\$\{([a-z0-9_]+)\}", xml)))
            yellow = len(re.findall(r'w:highlight w:val="yellow"', xml))
            print(part, "placeholders:", len(placeholders), "yellow:", yellow)
            print(" ", ", ".join(placeholders))


if __name__ == "__main__":
    main()
