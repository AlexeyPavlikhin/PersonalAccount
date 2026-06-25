#!/usr/bin/env python3
from __future__ import annotations

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
        xml = archive.read("word/document.xml")

    root = ET.fromstring(xml)
    parent_map: dict = {}

    def walk(node):
        for child in list(node):
            parent_map[child] = node
            walk(child)

    walk(root)

    in_table = 0
    outside = 0
    for run in root.iter(w_tag("r")):
        highlight = run.find(f".//{w_tag('highlight')}")
        if highlight is None or highlight.get(f"{{{W_NS}}}val") != "yellow":
            continue
        node = run
        inside = False
        while node in parent_map:
            node = parent_map[node]
            if node.tag == w_tag("tbl"):
                inside = True
                break
        if inside:
            in_table += 1
        else:
            outside += 1
            texts = [t.text or "" for t in run.iter(w_tag("t"))]
            print("OUT:", "".join(texts))

    print("in_table", in_table, "outside", outside)


if __name__ == "__main__":
    main()
