#!/usr/bin/env python3
"""Временный анализ жёлтых выделений в DOCX."""
from __future__ import annotations

import re
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "requirements" / "docs_temlpates" / "doc" / "Договор_оказания_юридических_услуг_ООО__Линки_.docx"


def main() -> None:
    with zipfile.ZipFile(SOURCE) as archive:
        xml = archive.read("word/document.xml").decode("utf-8")

    pattern = re.compile(r"<w:highlight w:val=\"yellow\"\s*/>")
    count = 0
    for match in pattern.finditer(xml):
        start = match.start()
        run_start = xml.rfind("<w:r", 0, start)
        run_end = xml.find("</w:r>", start)
        if run_start < 0 or run_end < 0:
            continue
        run_end += len("</w:r>")
        chunk = xml[run_start:run_end]
        texts = re.findall(r"<w:t[^>]*>([^<]*)</w:t>", chunk)
        text = "".join(texts)
        count += 1
        print(f"{count:02d}: {text!r}")

    print(f"total: {count}")


if __name__ == "__main__":
    main()
