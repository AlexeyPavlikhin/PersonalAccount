#!/usr/bin/env python3
from __future__ import annotations

import re
import zipfile
from pathlib import Path
from xml.etree import ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "requirements" / "docs_temlpates" / "doc" / "Договор_оказания_юридических_услуг_ООО__Линки_.docx"
W_NS = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"


def w_tag(name: str) -> str:
    return f"{{{W_NS}}}{name}"


def main() -> None:
    with zipfile.ZipFile(SOURCE) as archive:
        xml = archive.read("word/header1.xml").decode("utf-8")

    pattern = re.compile(r"<w:highlight w:val=\"yellow\"\s*/>")
    count = 0
    for match in pattern.finditer(xml):
        start = match.start()
        run_start = xml.rfind("<w:r", 0, start)
        run_end = xml.find("</w:r>", start) + 6
        chunk = xml[run_start:run_end]
        texts = re.findall(r"<w:t[^>]*>([^<]*)</w:t>", chunk)
        count += 1
        print(f"{count:02d}: {''.join(texts)!r}")


if __name__ == "__main__":
    main()
