#!/usr/bin/env python3
"""Reconstruct pg_documents.js by applying transcript patches in order."""
from __future__ import annotations

import json
import glob
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
TARGET = ROOT / "components" / "pg_documents.js"
TRANSCRIPTS = Path(r"C:\Users\VTB\.cursor\projects\c-MyProjects-msll-dev\agent-transcripts")

# Chronological order of transcript files (approximate development order)
ORDER = [
    "b4283ddc-a2dc-416f-aefa-6358af39446f",
    "410a12df-8e9b-4045-b780-c0a39cc1fccc",
    "3e8227b3-34a6-4b1d-b947-f3dba99c9646",
    "0cda5586-e688-49bb-a5eb-15fbc92c8cfa",
]


def collect_ops():
    ops = []
    seen_files = set()
    for prefix in ORDER:
        for fp in TRANSCRIPTS.rglob(f"{prefix}*.jsonl"):
            if str(fp) in seen_files:
                continue
            seen_files.add(str(fp))
            for line_no, line in enumerate(open(fp, encoding="utf-8"), 1):
                if "pg_documents.js" not in line:
                    continue
                try:
                    obj = json.loads(line)
                except json.JSONDecodeError:
                    continue
                content = obj.get("message", {}).get("content", [])
                if not isinstance(content, list):
                    continue
                for part in content:
                    if not isinstance(part, dict) or part.get("type") != "tool_use":
                        continue
                    inp = part.get("input")
                    if not isinstance(inp, dict):
                        continue
                    path = inp.get("path", "")
                    if not path.endswith("pg_documents.js"):
                        continue
                    if "contents" in inp:
                        ops.append(("write", inp["contents"], f"{fp.name}:{line_no}"))
                    elif "old_string" in inp and "new_string" in inp:
                        ops.append(
                            (
                                "replace",
                                inp["old_string"],
                                inp["new_string"],
                                f"{fp.name}:{line_no}",
                            )
                        )
                    elif "input" in inp and "Begin Patch" in str(inp["input"]):
                        patch = str(inp["input"])
                        m = re.search(
                            r"\+\+\+ b/components/pg_documents.js\n(.*?)(?=\n\*\*\* End Patch)",
                            patch,
                            re.S,
                        )
                        if m:
                            body = m.group(1)
                            # extract only + lines from add file patch
                            if "*** Add File:" in patch:
                                lines = []
                                for pl in body.splitlines():
                                    if pl.startswith("+") and not pl.startswith("+++"):
                                        lines.append(pl[1:])
                                ops.append(("write", "\n".join(lines) + "\n", f"{fp.name}:{line_no}"))
                            else:
                                ops.append(("patch_raw", patch, f"{fp.name}:{line_no}"))
    return ops


def apply_ops(ops):
    content = None
    applied = 0
    skipped = 0
    for op in ops:
        kind = op[0]
        if kind == "write":
            content = op[1]
            applied += 1
            print("WRITE", op[2], "len", len(content))
        elif kind == "replace":
            old, new, loc = op[1], op[2], op[3]
            if content is None:
                print("SKIP (no content)", loc)
                skipped += 1
                continue
            if old not in content:
                print("SKIP (old not found)", loc, old[:70].replace("\n", " "))
                skipped += 1
                continue
            content = content.replace(old, new, 1)
            applied += 1
            print("REPLACE", loc)
        else:
            print("SKIP patch_raw", op[2])
            skipped += 1
    return content, applied, skipped


def main():
    ops = collect_ops()
    print("ops", len(ops))
    content, applied, skipped = apply_ops(ops)
    if not content:
        print("FAILED: no content")
        return 1
    # Fix any motion tags
    content = content.replace("<motion ", "<div ").replace("</motion>", "</div>")
    if content.strip() == "-NoNewline":
        print("FAILED: still corrupted")
        return 1
    TARGET.write_text(content, encoding="utf-8")
    print("written", TARGET, "lines", content.count("\n") + 1, "applied", applied, "skipped", skipped)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
