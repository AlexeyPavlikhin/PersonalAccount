#!/usr/bin/env python3
from __future__ import annotations

import zipfile
from pathlib import Path
from xml.etree import ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "requirements" / "docs_temlpates" / "doc" / "Договор_оказания_юридических_услуг_ООО__Линки_.docx"
OUT = ROOT / "tools" / "_yellow_groups_report.txt"
W_NS = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"


def w_tag(name: str) -> str:
    return f"{{{W_NS}}}{name}"


def is_spec_table(table: ET.Element) -> bool:
    text_parts = []
    for node in table.iter(w_tag("t")):
        if node.text:
            text_parts.append(node.text)
    text = "".join(text_parts)
    return "Наименование услуги" in text or "Стоимость (руб.)" in text


def get_run_text(run: ET.Element) -> str:
    return "".join((node.text or "") for node in run.iter(w_tag("t")))


def is_yellow_run(run: ET.Element) -> bool:
    highlight = run.find(f".//{w_tag('highlight')}")
    return highlight is not None and highlight.get(f"{{{W_NS}}}val") == "yellow"


def main() -> None:
    with zipfile.ZipFile(SOURCE) as archive:
        root = ET.fromstring(archive.read("word/document.xml"))

    parent_map: dict = {}

    def walk(node):
        for child in list(node):
            parent_map[child] = node
            walk(child)

    walk(root)

    tables = list(root.iter(w_tag("tbl")))
    table_ids = {id(table): index + 1 for index, table in enumerate(tables)}
    skip_table_ids = {id(table) for table in tables if is_spec_table(table)}

    def zone_for_run(run: ET.Element) -> str:
        node = run
        while node in parent_map:
            node = parent_map[node]
            if node.tag == w_tag("tbl"):
                table_no = table_ids.get(id(node), 0)
                if id(node) in skip_table_ids:
                    return "SKIP_SPEC_TABLE"
                return f"TABLE_{table_no}"
        return "BODY"

    groups: list[tuple[str, list[str]]] = []
    current_zone = ""
    current_texts: list[str] = []

    for run in root.iter(w_tag("r")):
        if not is_yellow_run(run):
            if current_texts:
                groups.append((current_zone, current_texts))
                current_texts = []
                current_zone = ""
            continue

        zone = zone_for_run(run)
        text = get_run_text(run)
        if zone == current_zone and zone != "SKIP_SPEC_TABLE":
            current_texts.append(text)
        else:
            if current_texts:
                groups.append((current_zone, current_texts))
            current_zone = zone
            current_texts = [text] if zone != "SKIP_SPEC_TABLE" else []

    if current_texts:
        groups.append((current_zone, current_texts))

    lines = []
    for index, (zone, texts) in enumerate(groups, start=1):
        merged = "".join(texts)
        lines.append(f"{index:02d} [{zone}] {merged!r}")

    OUT.write_text("\n".join(lines) + f"\n\ntotal groups: {len(groups)}\n", encoding="utf-8")
    print(OUT)


if __name__ == "__main__":
    main()
