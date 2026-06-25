#!/usr/bin/env python3
"""
Конвертация DOCX «Договор … ООО Линки» в шаблон с плейсхолдерами ${field_code}.

Жёлтые выделения вне таблицы спецификации заменяются на плейсхолдеры.
Таблица «Наименование услуги / Стоимость» и сумма «120 000» не шаблонируются.
"""

from __future__ import annotations

import json
import re
import zipfile
from pathlib import Path
from xml.etree import ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
SOURCE_DOCX = (
    ROOT
    / "requirements"
    / "docs_temlpates"
    / "doc"
    / "Договор_оказания_юридических_услуг_ООО__Линки_.docx"
)
OUTPUT_DOCX = (
    ROOT
    / "requirements"
    / "docs_temlpates"
    / "doc"
    / "Договор_оказания_юридических_услуг_ООО__Линки__template.docx"
)
FIELD_MAP_JSON = ROOT / "tools" / "legal_services_linki_field_map.json"

W_NS = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
ET.register_namespace("w", W_NS)

# Порядок групп: word/header1.xml (3), затем word/document.xml (27; «120 000» пропускается).
YELLOW_GROUP_FIELDS = [
    # header — колонтитул «Договор … № … от …» (две жёлтые части номера + дата)
    "contract_number",
    "contract_number",
    "contract_date_display",
    # document — шапка договора
    "contract_number",
    "contract_day",
    "contract_month_words",
    "contract_year_yyyy",
    "company_name",
    "signer_position_genitive",
    "signer_name_genitive",
    "signer_basis",
    # table 1 — реквизиты заказчика
    "company_name",
    "address",
    "ogrn",
    "inn_kpp",
    "checking_account",
    "bank_name",
    "corr_account",
    "bik",
    "email",
    "phone",
    "signer_signature",
    # преамбула спецификации
    "contract_day",
    "contract_month_words",
    "contract_year_yyyy",
    "company_name",
    "signer_position_genitive",
    "signer_name_genitive",
    "contract_subject_short",
    # table 3 — подпись
    "signer_signature",
]

SKIP_GROUP_TEXT_RE = re.compile(r"^120[\s\u00a0]*000$")
XML_PARTS = (
    "word/header1.xml",
    "word/document.xml",
)


def w_tag(local_name: str) -> str:
    return f"{{{W_NS}}}{local_name}"


def get_run_text(run: ET.Element) -> str:
    return "".join((node.text or "") for node in run.iter(w_tag("t")))


def set_run_text(run: ET.Element, text: str) -> None:
    texts = list(run.iter(w_tag("t")))
    if not texts:
        text_node = ET.SubElement(run, w_tag("t"))
        text_node.text = text
        return
    texts[0].text = text
    for extra in texts[1:]:
        extra.text = ""


def is_yellow_run(run: ET.Element) -> bool:
    highlight = run.find(f".//{w_tag('highlight')}")
    return highlight is not None and highlight.get(f"{{{W_NS}}}val") == "yellow"


def remove_highlight(run: ET.Element) -> None:
    r_pr = run.find(w_tag("rPr"))
    if r_pr is None:
        return
    for highlight in list(r_pr.findall(w_tag("highlight"))):
        r_pr.remove(highlight)


def build_parent_map(root: ET.Element) -> dict[ET.Element, ET.Element]:
    parent_map: dict[ET.Element, ET.Element] = {}

    def walk(node: ET.Element) -> None:
        for child in list(node):
            parent_map[child] = node
            walk(child)

    walk(root)
    return parent_map


def is_spec_table(table: ET.Element) -> bool:
    text = "".join((node.text or "") for node in table.iter(w_tag("t")))
    return "Наименование услуги" in text or "Стоимость (руб.)" in text


def paragraph_of(run: ET.Element, parent_map: dict[ET.Element, ET.Element]) -> ET.Element | None:
    current: ET.Element | None = run
    while current is not None:
        if current.tag == w_tag("p"):
            return current
        current = parent_map.get(current)
    return None


def zone_for_run(
    run: ET.Element,
    parent_map: dict[ET.Element, ET.Element],
    table_ids: dict[int, int],
    skip_table_ids: set[int],
) -> str:
    current: ET.Element | None = run
    while current is not None:
        if current.tag == w_tag("tbl"):
            if id(current) in skip_table_ids:
                return "SKIP_SPEC_TABLE"
            table_no = table_ids.get(id(current), 0)
            return f"TABLE_{table_no}"
        current = parent_map.get(current)
    return "BODY"


def collect_yellow_groups(root: ET.Element, parent_map: dict[ET.Element, ET.Element]) -> list[tuple[str, list[ET.Element]]]:
    tables = list(root.iter(w_tag("tbl")))
    table_ids = {id(table): index + 1 for index, table in enumerate(tables)}
    skip_table_ids = {id(table) for table in tables if is_spec_table(table)}

    groups: list[tuple[str, list[ET.Element]]] = []
    current_zone = ""
    current_runs: list[ET.Element] = []
    current_paragraph_id: int | None = None

    for run in root.iter(w_tag("r")):
        if not is_yellow_run(run):
            if current_runs:
                groups.append((current_zone, current_runs))
                current_runs = []
                current_zone = ""
                current_paragraph_id = None
            continue

        zone = zone_for_run(run, parent_map, table_ids, skip_table_ids)

        if zone == "SKIP_SPEC_TABLE":
            if current_runs:
                groups.append((current_zone, current_runs))
                current_runs = []
            groups.append((zone, [run]))
            current_zone = ""
            current_paragraph_id = None
            continue

        paragraph = paragraph_of(run, parent_map)
        paragraph_id = id(paragraph) if paragraph is not None else id(run)

        if (
            current_runs
            and zone == current_zone
            and current_paragraph_id == paragraph_id
        ):
            current_runs.append(run)
            continue

        if current_runs:
            groups.append((current_zone, current_runs))

        current_zone = zone
        current_runs = [run]
        current_paragraph_id = paragraph_id

    if current_runs:
        groups.append((current_zone, current_runs))

    return groups


def merged_group_text(runs: list[ET.Element]) -> str:
    return "".join(get_run_text(run) for run in runs)


def apply_placeholders_to_root(root: ET.Element, field_index: int) -> int:
    parent_map = build_parent_map(root)
    groups = collect_yellow_groups(root, parent_map)

    for zone, runs in groups:
        if zone == "SKIP_SPEC_TABLE":
            for run in runs:
                remove_highlight(run)
            continue

        text = merged_group_text(runs)
        if SKIP_GROUP_TEXT_RE.match(text.strip()):
            for run in runs:
                remove_highlight(run)
            continue

        if field_index >= len(YELLOW_GROUP_FIELDS):
            raise RuntimeError(
                f"Too many yellow groups ({field_index + 1}), no field mapping for {text!r}"
            )

        field_code = YELLOW_GROUP_FIELDS[field_index]
        field_index += 1
        placeholder = f"${{{field_code}}}"

        set_run_text(runs[0], placeholder)
        remove_highlight(runs[0])
        for extra_run in runs[1:]:
            set_run_text(extra_run, "")
            remove_highlight(extra_run)

    return field_index


def template_services_table(root: ET.Element) -> None:
    """Шаблонная строка таблицы услуг: маркер ${#services} и плейсхолдеры колонок."""
    for table in root.iter(w_tag("tbl")):
        if not is_spec_table(table):
            continue
        rows = list(table.findall(w_tag("tr")))
        if len(rows) < 2:
            continue
        data_row = rows[1]
        cells = list(data_row.findall(w_tag("tc")))
        column_codes = ("service_name", "service_price")
        for cell_index, field_code in enumerate(column_codes):
            if cell_index >= len(cells):
                break
            paragraphs = list(cells[cell_index].iter(w_tag("p")))
            if not paragraphs:
                continue
            paragraph = paragraphs[0]
            runs = list(paragraph.findall(w_tag("r")))
            if not runs:
                runs = [ET.SubElement(paragraph, w_tag("r"))]
            if cell_index == 0:
                placeholder = "${#services} ${service_name}"
            else:
                placeholder = f"${{{field_code}}}"
            set_run_text(runs[0], placeholder)
            remove_highlight(runs[0])
            for extra_run in runs[1:]:
                set_run_text(extra_run, "")
                remove_highlight(extra_run)
        break


def build_field_map_json() -> str:
    # В БД достаточно {}: все ключи form_data/enrich подставляются как ${field_code}.
    # Особый маппинг — только при расхождении имён (см. legal_services_yul_field_map.json).
    return "{}"


def process_docx() -> None:
    updated_parts: dict[str, bytes] = {}

    with zipfile.ZipFile(SOURCE_DOCX, "r") as archive:
        field_index = 0
        for part_name in XML_PARTS:
            if part_name not in archive.namelist():
                continue
            root = ET.fromstring(archive.read(part_name))
            field_index = apply_placeholders_to_root(root, field_index)
            if part_name == "word/document.xml":
                template_services_table(root)
            updated_parts[part_name] = ET.tostring(root, encoding="utf-8", xml_declaration=True)

        if field_index != len(YELLOW_GROUP_FIELDS):
            raise RuntimeError(
                f"Field mapping mismatch: used {field_index} of {len(YELLOW_GROUP_FIELDS)}"
            )

        OUTPUT_DOCX.parent.mkdir(parents=True, exist_ok=True)
        with zipfile.ZipFile(OUTPUT_DOCX, "w", zipfile.ZIP_DEFLATED) as output_archive:
            for item in archive.infolist():
                data = archive.read(item.filename)
                if item.filename in updated_parts:
                    data = updated_parts[item.filename]
                output_archive.writestr(item, data)

    FIELD_MAP_JSON.write_text(build_field_map_json() + "\n", encoding="utf-8")
    print(f"Written template: {OUTPUT_DOCX}")
    print(f"Written field map: {FIELD_MAP_JSON}")


def main() -> None:
    if not SOURCE_DOCX.exists():
        raise FileNotFoundError(SOURCE_DOCX)
    process_docx()


if __name__ == "__main__":
    main()
