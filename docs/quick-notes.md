# Quick notes

Quick notes are lightweight, personal captures — somewhere to jot an idea that
doesn't yet belong on a board.

## What it does

Create a note from the command palette ("New note") or the **Notes** panel on the
dashboard. A note has a title and an optional rich-text body — the same editor used
for descriptions, with inline images. Your notes are listed on the dashboard, newest
first, where you can edit, share or delete them.

## Privacy & sharing

- A note is **private to you** by default and need not belong to any project.
- Attach a note to a project you're a member of, and you can mark it **public**. A
  public note appears, read-only, in that project's **Notes** section for every
  member; private attached notes stay yours alone.
- A note can only be public while it's attached to a project — detaching it (or never
  attaching it) forces it private again.
- Only the owner can edit, re-share or delete a note. Inline images in a public note
  are viewable by the project's members.

## Convert to task

Turn a note into a task in one step ("Convert to task"). The create-task dialog opens
prefilled with the note's title and body; choose the project (and optionally a parent
task) and save. The note is kept and shows a **Converted → PROJ-N** badge linking to
the task it produced.

## AI / MCP

The MCP server exposes note tools — create, list, get, update and convert — so an AI
agent can manage notes on your behalf. Notes are referenced by a plain numeric id,
outside the `PROJ-N` task namespace. Listing returns your own notes plus public notes
in your projects; creating, updating and converting require a write-access token.
