#!/usr/bin/env python3
from __future__ import annotations

import zipfile
from pathlib import Path
from xml.etree import ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "requirements" / "docs_temlpates" / "doc" / "Договор_оказания_юридических_услуг_ООО__Линки_.docx"
W_NS = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
OUT = ROOT / "tools" / "_tables_report.txt"


def w_tag(name: str) -> str:
    return f"{{{W_NS}}}{name}"


def table_text(table: ET.Element) -> str:
    parts = []
    for t in table.iter(w_tag("t")):
        if t.text:
            parts.append(t.text)
    return "".join(parts)


def main() -> None:
    with zipfile.ZipFile(SOURCE) as archive:
        root = ET.fromstring(archive.read("word/document.xml"))

    lines: list[str] = []
    for index, table in enumerate(root.iter(w_tag("tbl")), start=1):
        text = table_text(table)
        yellow = 0
        for run in table.iter(w_tag("r")):
            highlight = run.find(f".//{w_tag('highlight')}")
            if highlight is not None and highlight.get(f"{{{W_NS}}}val") == "yellow":
                yellow += 1
        lines.append(f"=== table {index} yellow={yellow} len={len(text)} ===")
        lines.append(text[:500].replace("\n", " "))
        lines.append("")

    OUT.write_text("\n".join(lines), encoding="utf-8")
    print(f"written {OUT}")


if __name__ == "__main__":
    main()
