# ToDo

## Images
- Add images to projects, including the logo/icon.
- Allow setting profile picture.

## Make audit log collapsible
- Make it collapsed by default.

## Allow collapsing of comments
- Store the state of collapsed comments as a preference

## MCP Server
MCP support at `/mcp`. Agents are authorized by a user via a personal API token
(read-only or read & write) and act in that user's name, restricted to projects
they can access. Done:
- Read access to projects, stories and tasks (any token).
- Creating projects (requires the "create-projects" permission), stories and tasks.
- Managing stories and tasks (title, description, task status).
- Adding comments to projects, stories and tasks.

Write tools require a token with the `write` ability.

# E-Mail updates
- Add optional e-mail updates on the project, story and task level.
- Setting for immediate e-mail updates or for specific time intervals.

## Stories from e-mails
- Add stories from e-mails.
- Support for attachments.

## Command palette
- Add a command palette with keyboard shortcuts and quick access.
- Include search functionality.

## Due dates
- Add optional due dates to stories and tasks.

## Prettier drag-and-drop
- Add prettier drag-and-drop.

## Add user administration
- Add user administration.
- Maybe an admin permission?

## Generate project short name
If a project title is entered and there is no short name yet, the short name should be generated from the title.
The short name should be generated from the project title by uppercasing the first three letters.  
As soon as there are three or more words in the title, the short name should be generated from the first letters of the first three (or four) words.

## Add @mentions

## Add tagging tasks and stories in descriptions and comments

## Improve visuals
- The project looks a little bit ugly.
- Maybe Claude Design can help?

## Testing
- Add browser tests.

## Login
- Change the tab index to not go up from the password field to the "forgot password" link, but down to the "remember me" checkbox and then to the login button.
