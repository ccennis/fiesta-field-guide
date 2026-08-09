# Project Development Guide

## Code Style Guidelines
- **Framework**: Laravel PHP (PSR standards)
- **PHP Version**: 8.3+
- **Formatting**: Laravel Pint (preset: laravel)
- **Namespacing**: PSR-4 with App\\ namespace
- **Imports**: Always use `use` statements at the top of the file — never inline fully qualified class names
- **Models**: Located in `app/Models` with appropriate relationships
- **Folder Structure**: Follow Laravel conventions (Controllers, Services, Jobs, etc.)
- **Error Handling**: Use Laravel exceptions and proper try/catch blocks
- **Documentation**: DocBlocks on classes and complex methods

## Controllers
- Controllers should be thin — business logic belongs in Services (`app/Services/`)
- No logic in controllers beyond calling the service and returning a response
- Early returns over nested conditionals

## Enums & Constants
- Always use enums for status values, types, and string constants — never raw strings
- Enums live in `app/Enums/`
- When adding a new status or type, add it to the relevant enum first, then reference the constant

## API Response Guidelines
- Always use a consistent response shape: `{ success, message, data, errors }`
- Use Laravel Resources — never return hardcoded response arrays
- Resources live in `app/Http/Resources/`

### Example
```php
// Good
return $this->success(new PostResource($post));

// Bad
return response()->json(['id' => $post->id, 'title' => $post->title]);
```

## Email & Notifications
- Always use Laravel Notifications — never use the Mail facade directly
- For platform users: `$user->notify(new SomeNotification(...))`
- For external recipients: `Notification::route('mail', $email)->notify(new SomeNotification(...))`
- Notification classes live in `app/Notifications/`

## Scope & Focus
- When implementing a feature, only modify files directly related to the task
- Do not refactor unrelated code even if it looks improvable
- Do not add unrequested features or "nice to haves"
- If something is ambiguous, stop and ask rather than assume

## Output & Verification
- After generating any code, summarize what was created, what files were changed, and what still needs to be done
- Flag any assumptions made during implementation
- When multiple implementation approaches exist, briefly explain the tradeoff between them before proceeding — do not pick one silently

## Pattern Consistency
- Before implementing anything new, check existing code for established patterns and match them exactly
- Do not introduce a second way of doing something that already exists in the codebase
- Prefer explicit over clever — no unnecessary abstraction

## Database & Migrations
- Never modify existing migrations — always create new ones
- Never rename or reorder existing migration files
- Migrations should be focused and single-purpose

## Dependencies
- Before adding any package, ask for approval
- Prefer solving problems with what the framework already provides
- No extra packages unless explicitly requested

## Testing Guidelines
- All tests follow Laravel testing conventions
- All test methods must be prefixed with `test`
- Mocking reserved for complex situations — use real models where possible

## General Rules
- Keep comments to a minimum
- No emojis in code or comments
- Do not make proactive decisions about implementation — always present options and wait for direction before writing code
- Do not perform any git operations — git is handled manually
- No extra packages unless explicitly asked for

## Domain Data
- Fiesta reference data (colors, production years, shapes, marks, lines) is supplied by
  the project owner. Never generate, guess, or fill in reference data.
- Source data lives in `database/seed-data/` and is a real collection export. It is
  intentionally not cleaned. Do not "fix" it at the source.
- When source data is ambiguous or two sources disagree, surface the conflict for a
  decision. Never silently pick a resolution.
- If catalog data is needed and has not been provided, stop and ask.

## Constraints
- No external API calls. The system must run fully offline with no API keys.
- Timeboxed exercise. Prefer the simplest thing that works over the most complete thing.
- A reviewer must be able to clone and run this with `composer setup` then `composer dev`
  and nothing else. Do not add setup steps.
