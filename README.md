## Self-scoring

Mark all completed tasks with [X] symbol. Reminder: all minimum requirements MUST be completed, otherwise the home assignment will be rejected.

- **Environment**
  - [X] Fill out the README.hun.md file in the starter package (declaration, points).
  - [X]The site must be created without PHP frameworks (e.g., Laravel).
- **Homepage, guest user**
  - [X] Displays a list of published/approved projects (if statuses are used).
  - [X] Clicking a project opens its details.
  - [X] Projects can be filtered by category via a dropdown.
- **Authentication**
  - [X] Username must be unique.
  - [X] Password at least 8 characters.
  - [X] Registration enables login with the created user.
  - [X] Logout works.
  - [X] There exists an admin user with username admin and password admin.
  - [X] Passwords are stored hashed.
- **Homepage, logged-in user**
  - [X] The user can submit a new project (for minimum requirements it may immediately be set to approved / appear in the list; normally it should go to pending).
  - [X] Project title minimum 10 characters.
  - [X] Project description minimum 150 characters.
  - [X] ID, owner, and submission date are set automatically.

### Core tasks (12 points)

- **Homepage**
    - [X] 0.5 pts: Projects appear grouped by category.
    - [X] 0.5 pts: Logged-in users can vote from the list; it is visible which projects they have voted for.
    - [X] 0.5 pts: A user can vote for a project only once.
    - [X] 1.0 pts: A user can cast at most 3 votes per category. Remaining votes per category are displayed.
    - [X] 0.5 pts: Votes can be withdrawn.

- **Forms**
  - Registration:
    - [X] 0.5 pts: Username cannot contain spaces.
    - [X] 0.5 pts: Email format must be valid.
    - [X] 0.5 pts: Password must include lowercase, uppercase, and numeric characters.
    - [X] 0.5 pts: The two password fields must match.
  - New project / project update:
    - [X] 0.5 pts: Category selectable only from this fixed list:
      - `Local small project`, `Local large project`, `Equal opportunity Budapest`, `Green Budapest`
    - [X] 1.0 pts: Postal code valid format.
      - 0.5 partial: any 4-digit integer ≥1000
      - Full: first digit 1, next two 01–23, last digit 1–9, plus 1007 allowed.
    - [X] 0.5 pts: Image URL optional, but if provided, must be valid.
- **Unpublished projects**
    - [X] 0.5 pts: Users can view their non-approved projects (`pending`, `rework`, `rejected`) and open their details.
    - [X] 1.0 pts: The project details page cannot be viewed (even with direct URL) unless the user is the owner or an admin; others must be redirected.
- **Admin**
    - [X] 0.5 pts: Admin sees all `pending` projects on one page.
    - [X] 1.0 pts: Admin can publish (`approved`) or reject (`rejected`) pending projects.
    - [X] 0.5 pts: Admin sees the project with the most votes.
    - [X] 0.5 pts: Admin sees the top 3 projects per category.

### Extra tasks (5 points)

- [X] 1.0 pts: Voting implemented with AJAX/Fetch without page reload.
- [X] 1.0 pts: Admin can return a pending project for rework (rework) with a comment; the user can edit it and resubmit (pending). This can repeat indefinitely.
- [X] 1.0 pts: During pending–rework cycles, not only the latest admin comment is visible but all of them; and the old/new values of modified fields are stored and displayed.
- [X] 2.0 pts: Admin can view the number of projects grouped by category AND status.
  - [X] 0.5 partial: list or table
  - Full: two charts