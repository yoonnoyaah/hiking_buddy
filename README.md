# Hiking Buddy Community Application

This project is organized into separate `member/` and `admin/` folders.

## Member application
The existing React + Tailwind + Vite member-facing application is in `member/`. The root Vite configuration and package files are retained for a simple local development workflow.

Run from the project root:

```bash
npm install
npm run dev
```

Member frontend: `http://localhost:5173/`  
Admin dashboard: `http://localhost:5173/admin/`

If Vite does not resolve the admin directory index on your setup, open `http://localhost:5173/admin/index.html`.

## Admin dashboard features
- KPI summary cards for registered members, monthly trips, trails, and member joins
- User growth line chart and popular mountains bar chart (Chart.js)
- Member search/filter, ban/unban and delete demo actions
- Trip search/filter and remove moderation action
- Report flags with dismiss, action-taken, and reported-trip removal actions
- Platform announcements, recent activity log, JSON export
- Responsive sidebar and mobile navigation

## Important limitations
Both sides are frontend prototypes. Admin data is seeded demo data saved in browser localStorage, separate from the member application's localStorage. The dashboard does not yet read real registered members or trips. Before production, connect it to Laravel API endpoints, enforce admin authorization server-side, hash passwords, validate requests, and audit moderation actions on the server. Never rely on a frontend role or localStorage as access control.

## Community and trip-posting features
- Community feed: members can publish posts with text and an uploaded photo, like posts, and add comments. Each member can like a post once; clicking Like again removes the like.
- Photo uploads are stored as browser-local data for this prototype. The upload limit is 2 MB per image.
- Reporting: members can report other members' community posts and trip posts. The author does not see a Report button on their own community post or trip post.
- Add a mountain / region: this is built into the **Post Your Trip** form. Choose the add-new option in the mountain or region dropdown, enter the missing name, and publish the trip. The new place is also sent to the admin for review. Approved place suggestions can be added to the trail directory.

These features use browser localStorage and are intended for a front-end prototype. Data is not shared across devices and can be lost if browser storage is cleared. For production, connect the app to Laravel/MySQL APIs, store uploaded photos on a server or object storage, and enforce authentication, authorization, reporting and moderation on the server.
