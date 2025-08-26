# Emoncms Dashboard Module

Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

Emoncms Dashboard is a PHP web application module that provides drag-and-drop visual custom dashboard builder functionality for the Emoncms energy monitoring system. It creates interactive dashboards with widgets like dials, gauges, graphs, and controls for visualizing energy data.

## Working Effectively

### Bootstrap and validate the repository:
- Install dependencies: `composer install --no-interaction` -- takes 1-2 seconds. NEVER CANCEL.
- Verify installation: `composer test` -- takes <1 second. NEVER CANCEL.
  - This runs PHP parallel lint on 31 PHP files
  - Alternative validation: `find . -name "*.php" -not -path "./vendor/*" | xargs php -l`
- Test security filtering: `php AntiXSS/filter_test.php` 
  - Should output "Modified output:" showing XSS sanitization working

### Requirements and dependencies:
- PHP 8.0+ with mbstring extension (verified with `php -m | grep mbstring`)
- Composer for dependency management
- MySQL database (for full Emoncms installation)
- Web server (Apache, Nginx, or PHP built-in server for development)

### Development server:
- Start development server: `php -S localhost:8000`
- Test static file access: Static files (JS, CSS) are accessible at `http://localhost:8000/`
- Widget files accessible at: `http://localhost:8000/widget/[widget-name]/[widget-name]_render.js`
- IMPORTANT: This module requires the main Emoncms system for full functionality

### Testing and validation:
- Always run `composer test` before committing changes
- Test the AntiXSS filtering with `php AntiXSS/filter_test.php` when modifying security-related code
- Verify PHP syntax with `php -l [filename.php]` for individual files

## Project Structure

### Key directories:
- `widget/` -- Contains 23 widget types (dial, battery, button, etc.)
- `Views/` -- PHP views and JavaScript frontend code
- `AntiXSS/` -- XSS protection library (PHP5 and PHP7 versions)
- `locale/` -- Internationalization files

### Core files:
- `dashboard_controller.php` -- Main controller handling requests
- `dashboard_model.php` -- Database model for dashboard CRUD operations 
- `dashboard_schema.php` -- Database schema definition
- `Views/js/designer.js` -- Frontend dashboard designer interface
- `Views/js/render.js` -- Widget rendering system

### Widget system:
Each widget has a `[widget-name]_render.js` file in `widget/[widget-name]/` directory.
Example widget types: dial, battery, button, cylinder, dewpoint, feedvalue, jgauge, led, thermometer

## Validation Scenarios

### Always test after making changes:
1. Run syntax check: `composer test` (must complete in <1 second)
2. Test security filtering: `php AntiXSS/filter_test.php` 
3. Start dev server: `php -S localhost:8000` and verify static files load
4. For widget changes: Verify widget JS files are accessible via HTTP

### Manual testing scenarios:
- **Dashboard creation workflow**: This module works within Emoncms - create dashboard, add widgets, configure properties
- **Widget rendering**: Test that widgets render correctly with various data feeds
- **Security**: Verify XSS protection strips malicious code from dashboard content

## Common Tasks

### Adding a new widget:
1. Create directory: `widget/[widget-name]/`
2. Create JavaScript file: `widget/[widget-name]/[widget-name]_render.js`
3. Follow existing widget patterns for `[widget]_widgetlist()` function
4. Test widget loading via development server

### Modifying existing widgets:
1. Edit the `[widget-name]_render.js` file
2. Test with `composer test` for syntax
3. Start dev server and verify widget loads: `curl http://localhost:8000/widget/[widget-name]/[widget-name]_render.js`

### Database changes:
- Modify `dashboard_schema.php` for table structure changes
- Main table: `dashboard` with fields: id, userid, content, height, name, alias, description, public, etc.

### Security considerations:
- Always use AntiXSS filtering for user-generated content
- Test with `php AntiXSS/filter_test.php` after security-related changes
- Dashboard content is stored as HTML and must be sanitized

## Installation Context

This module is designed to be installed as part of the larger Emoncms system:
- Intended location: `/var/www/emoncms/Modules/dashboard`
- Requires Emoncms core system for database connections and user management
- Installation command in Emoncms context: Admin > Check for database updates

### For standalone development:
- Clone: `git clone https://github.com/emoncms/dashboard`
- Install: `composer install --no-interaction`
- Validate: `composer test && php AntiXSS/filter_test.php`

### Required system packages:
- `php-mbstring` extension (install with `sudo apt-get install php-mbstring`)
- Composer (for development dependencies)

## Performance and Timing Expectations

- `composer install`: 1-2 seconds (may require GitHub authentication)
- `composer test`: <1 second (lints 31 PHP files)
- `php -S localhost:8000`: Starts immediately
- AntiXSS test: <1 second
- Widget file serving: Immediate response

NEVER CANCEL any of these operations - they complete very quickly.