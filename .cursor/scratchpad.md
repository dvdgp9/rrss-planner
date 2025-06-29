# Project Scratchpad

## Background and Motivation

The user wants to refactor the application's page structure. Currently, there is one page per line of business. The goals are:
1.  Unify these multiple pages into a single, dynamic page.
2.  Allow users to create new lines of business directly from within the application.
3.  Improve maintainability and ease of making future changes.
4.  Ensure all existing functionalities are preserved in the new structure.

This change aims to make the application more scalable and flexible.

**PREVIOUS REQUEST (COMPLETED):** The user requested two UI improvements:
1. Set the "Mostrar publicados" toggle to be unchecked by default (currently it's checked by default)
2. Remove the time/hour display from the "Publicaciones Programadas y Pasadas" table, showing only dates since the publication creation form only allows date selection (not time)

**NEW REQUEST (Current - Social Media Publishing):** The user wants to evaluate the complexity of implementing direct social media publishing functionality. Currently, the system only manages and schedules posts but doesn't actually publish them to social media platforms. The goal is to understand what would be required to add automatic publishing capabilities to platforms like Instagram, Facebook, Twitter/X, and LinkedIn.

**NEW REQUEST:** Análisis UI/UX para mejoras de interfaz y adición de funcionalidad de blogs

El usuario necesita evaluar la interfaz actual (index.php y planner.php) para preparar la adición de publicaciones de blogs junto a las de redes sociales, manteniendo separación por línea de negocio.

**NEW REQUEST (COMPLETED):** Implementación de Interfaz con Selector de Línea de Negocio (Opción 1)

El usuario ha aprobado la implementación de la Opción 1: Selector de Línea en Header con interfaz simplificada tipo Mixpost. Esta fue completada exitosamente con funcionalidad completa de blog posts.

**NEW REQUEST (CURRENT - WordPress Integration):** Implementación de "Publicar en WordPress"

El usuario quiere implementar funcionalidad para publicar automáticamente los blog posts creados en el sistema directamente a las webs de WordPress de cada línea de negocio. Cada línea de negocio tiene su propia web en WordPress independiente.

**PROGRESS UPDATE - WordPress Categories & Tags Integration:**
El usuario ha solicitado que se implementen las categorías y etiquetas de WordPress para poder seleccionarlas al publicar. Se ha completado la implementación de la funcionalidad para obtener dinámicamente las categorías y etiquetas de cada sitio WordPress y permitir su selección en el formulario de blog posts.

**NEW ISSUE IDENTIFIED (CURRENT - WordPress Categories Bug):**
El usuario reporta que existe un problema con las categorías al publicar en WordPress. Cuando se pulsa "Publicar en WordPress", el blog post se está subiendo como "Uncategorized" aunque esté marcada una categoría específica en el formulario. Necesita análisis para localizar y solucionar el fallo en el sistema de categorías.

## Key Challenges and Analysis

*   **Current Architecture:** The application uses a static, multi-page approach where each line of business has its dedicated PHP file (e.g., `cubofit.php`, `teia.php`). These files contain both PHP backend logic (data fetching, sorting, filtering for that specific line of business) and HTML frontend structure.
*   **Redundancy:** Each "line of business" PHP file is almost identical, differing only by hardcoded variables like `lineaNombre`, `lineaId`, logo path, and the active navigation link. This leads to significant code duplication.
*   **Limitations of Current Architecture:**
    *   Adding new lines of business requires copying, pasting, and modifying an existing PHP file, which is error-prone and not scalable.
    *   Maintaining and updating shared functionalities or UI elements across many pages is inefficient.
*   **Proposed Solution:** Transition to a single-page application (SPA-like) model where a single PHP file (e.g., `business_line.php` or `planner.php`) can dynamically display content and functionality for different lines of business. This page will take a parameter (e.g., `linea_id` or a slug) to determine which line of business to display. Implement a feature to manage (create, read, update, delete) lines of business, likely involving new database tables and API endpoints.
*   **Potential Challenges:**
    *   **Routing/State Management:** The unified page will need to accept a parameter (e.g., `GET` parameter like `?id=LINEA_ID`) to identify the line of business.
    *   **Data Architecture:** Designing a robust way to manage data for "lines of business" (e.g., their names, IDs, logo paths, associated social networks). The `lineas_negocio` table already exists and is used.
    *   **UI/UX Design:** The main change will be how a user selects a line of business to view. Instead of direct navigation links for each, there might be a dropdown or a list on a central page. The individual "line of business view" should remain very similar to the current one.
    *   **Component Reusability:** The existing structure is already a template. The main task is to make the parts that vary (name, ID, logo) dynamic based on the selected line of business.
    *   **Backend Support:** The backend will need to support CRUD operations for "lines of business" themselves, allowing administrators to add/edit them. The existing data fetching logic for publications can be largely reused, just parameterized by the line of business ID.

**Analysis of Current "Mostrar publicados" Toggle and Time Display Issues:**

*   **Toggle Current State:** The toggle is currently implemented with `<input type="checkbox" id="toggle-published" checked>` across all pages (`planner.php`, `cubofit.php`, `teia.php`, `ebone.php`, `uniges.php`, `share_view.php`). The `checked` attribute makes it enabled by default.
*   **Toggle Functionality:** JavaScript in `assets/js/main.js` (lines 176-210) handles the toggle functionality:
    - It finds rows with `data-estado="publicado"` 
    - When toggle is unchecked, it hides published posts (`display: none`)
    - When toggle is checked, it shows all posts (default state)
    - The logic works by checking `toggleSwitch.checked` and applying display styles accordingly
*   **Time Display Issue:** In the publications table, dates are displayed using PHP `date("d/m/Y H:i", strtotime($publicacion['fecha_programada']))` (line 225 in `planner.php`), which shows both date and time.
*   **Form Date Input:** The `publicacion_form.php` uses `<input type="date">` for date selection (confirmed from examining the form structure), which only captures dates without time.
*   **Database Storage:** The `fecha_programada` field likely stores date with time, but since the form only captures dates, the time portion is probably set to `00:00:00` by default.
*   **Consistency Issue:** All pages with the toggle need to be updated for consistency:
    - `planner.php` (main unified page)
    - Legacy pages: `cubofit.php`, `teia.php`, `ebone.php`, `uniges.php`
    - `share_view.php` (public sharing page)

## Key Challenges and Analysis (Social Media Publishing Evaluation)

### Current System Architecture Assessment

The RRSS-planner currently operates as a content management and scheduling system with the following characteristics:

**✅ Existing Strengths:**
- Well-structured database schema with `publicaciones`, `redes_sociales`, and `linea_negocio_red_social` tables
- Multi-business line support through unified `planner.php` system
- Publication states management: `borrador`, `programado`, `publicado`
- Image upload and storage functionality
- Content scheduling with date/time planning
- User authentication and permission system

**❌ Missing Components for Direct Publishing:**
- No API integration infrastructure for social media platforms
- No OAuth token management system
- No job queue/cron system for scheduled publishing
- No external API error handling and retry mechanisms
- No webhook handling for API status updates

### Platform-Specific Complexity Analysis

Based on research of current social media APIs, here's the complexity breakdown:

**1. Instagram (HIGHEST COMPLEXITY - 🔴)**
- **Current Status:** Instagram Basic Display API deprecated December 4, 2024
- **New Requirement:** Must use Instagram Graph API with Business/Creator accounts only
- **Major Challenges:**
  - Requires Facebook Business account and Page linkage
  - Business verification process required for public use
  - App review process for advanced permissions
  - Rate limits: 100 posts per 24 hours per account
  - Content restrictions: JPEG only, specific aspect ratios
  - Stories have 24-hour expiration
- **Technical Requirements:**
  - OAuth 2.0 flow with token refresh (60-day expiration)
  - Webhooks server for status notifications
  - Two-step publishing process: create container → publish
  - Video upload requirements: resumable upload for large files

**2. Facebook (HIGH COMPLEXITY - 🟡)**
- **Integration:** Via Facebook Graph API (same as Instagram)
- **Challenges:**
  - Requires Facebook Page for business publishing
  - App review for public API access
  - Complex permission system
  - Rate limiting: varies by endpoint
- **Advantages:**
  - Mature API with extensive documentation
  - Unified with Instagram through Meta platform

**3. Twitter/X (MEDIUM-HIGH COMPLEXITY - 🟡)**
- **Current Status:** API v2 is current standard
- **Challenges:**
  - Paid API access (Basic tier starts ~$100/month)
  - Rate limits: varies by endpoint and plan
  - Character limits and media size restrictions
  - Recent policy changes affecting third-party access
- **Requirements:**
  - OAuth 1.0a or OAuth 2.0 authentication
  - App approval process

**4. LinkedIn (MEDIUM COMPLEXITY - 🟠)**
- **Integration:** LinkedIn Marketing API / Share API
- **Challenges:**
  - Requires LinkedIn Business account for company posting
  - App review process for production use
  - Rate limits: varies by use case
  - Content format restrictions
- **Advantages:**
  - Professional focus aligns with business use case
  - Relatively stable API

## Analysis of WordPress Categories Bug Issue

### Problem Description
Al publicar blog posts a WordPress usando el botón "Publicar en WordPress", los posts aparecen con la categoría "Uncategorized" aunque se hayan seleccionado categorías específicas en el formulario.

### Root Cause Analysis

**ISSUE IDENTIFIED:** Problema en el flujo de datos de categorías de WordPress

1. **Flujo Actual de Datos:**
   - `blog_form.php` carga dinámicamente categorías de WordPress vía JavaScript
   - Las categorías se muestran como checkboxes con `name="wp_categories[]"`
   - Al hacer submit, los datos se envían vía `publishToWordPress()` function
   - `publish_to_wordpress.php` recibe `$_POST['wp_categories']`
   - Los IDs se pasan a `WordPressAPI->publishPost()`

2. **Potential Issues Identified:**
   - **Issue A:** JavaScript no está enviando correctamente los datos de categorías seleccionadas
   - **Issue B:** El FormData en `publishToWordPress()` no está capturando las categorías correctamente
   - **Issue C:** La función `handleCategories()` en WordPressAPI no está funcionando como esperado
   - **Issue D:** Los IDs de categorías de WordPress no están siendo mapeados correctamente

3. **Critical Code Points to Investigate:**
   ```javascript
   // En blog_form.php línea ~760
   const wpCategories = [];
   document.querySelectorAll('.wp-category-checkbox:checked').forEach(checkbox => {
       wpCategories.push(checkbox.value);
   });
   
   // En publish_to_wordpress.php línea ~35
   $wp_categories = isset($_POST['wp_categories']) ? $_POST['wp_categories'] : [];
   
   // En WordPressAPI.php línea ~54
   if (!empty($post_data['wp_categories'])) {
       $wp_data['categories'] = array_map('intval', $post_data['wp_categories']);
   }
   ```

4. **Debugging Strategy Needed:**
   - Verificar que las categorías de WordPress se estén cargando correctamente en el formulario
   - Confirmar que los checkboxes tengan los valores correctos (IDs de WordPress)
   - Validar que el JavaScript esté enviando los datos correctamente
   - Comprobar que el endpoint PHP esté recibiendo las categorías
   - Verificar que la API de WordPress esté recibiendo los IDs correctos

### Technical Implementation Challenges

**1. OAuth Token Management (HIGH COMPLEXITY)**
- **Challenge:** Each platform requires secure token storage and automatic refresh
- **Requirements:**
  - Encrypted database storage for tokens
  - Background processes for token refresh
  - Graceful handling of expired/revoked tokens
  - Per-business-line token isolation

**2. API Rate Limiting & Queue Management (HIGH COMPLEXITY)**
- **Challenge:** Each platform has different rate limits that must be respected
- **Requirements:**
  - Intelligent queuing system
  - Rate limit tracking per platform per account
  - Backoff and retry logic
  - Priority handling for time-sensitive posts

**3. Content Format Adaptation (MEDIUM COMPLEXITY)**
- **Challenge:** Each platform has different content requirements
- **Requirements:**
  - Image format conversion (Instagram requires JPEG)
  - Aspect ratio adjustments per platform
  - Text length truncation/adaptation
  - Hashtag and mention formatting differences

**4. Error Handling & Reliability (HIGH COMPLEXITY)**
- **Challenge:** External APIs can fail, have downtime, or change requirements
- **Requirements:**
  - Comprehensive error logging and reporting
  - Graceful degradation when platforms are unavailable
  - User notification system for failed posts
  - Manual retry capabilities

**5. Webhook Infrastructure (MEDIUM COMPLEXITY)**
- **Challenge:** Many platforms use webhooks for status updates
- **Requirements:**
  - Secure webhook endpoint
  - SSL certificate for HTTPS
  - Signature verification for security
  - Background processing of webhook events

### Security & Compliance Considerations

**1. Data Privacy (HIGH IMPORTANCE)**
- GDPR compliance for EU users
- Secure storage of user social media credentials
- Clear consent mechanisms for publishing permissions
- Data retention and deletion policies

**2. API Key Security (CRITICAL)**
- Secure storage of app secrets and client IDs
- Environment-specific configuration management
- Regular rotation of API credentials
- Audit logging for API access

### Infrastructure Requirements

**1. New Database Tables Needed:**
```sql
-- OAuth tokens per business line per platform
social_media_tokens (
  id, linea_negocio_id, platform, access_token_encrypted, 
  refresh_token_encrypted, expires_at, created_at, updated_at
)

-- Publishing queue and status tracking
publication_queue (
  id, publicacion_id, platform, status, scheduled_for, 
  attempts, last_error, external_post_id, created_at, updated_at
)

-- Platform-specific configuration
platform_configs (
  id, platform, app_id, app_secret_encrypted, webhook_secret_encrypted,
  is_active, rate_limit_config, created_at, updated_at
)
```

**2. Background Processing System:**
- Cron jobs or queue workers for scheduled publishing
- Monitoring and alerting for failed jobs
- Logging and analytics for publishing success rates

### Cost Analysis

**API Access Costs (Monthly Estimates):**
- Instagram/Facebook: Free tier available, paid for high volume
- Twitter/X: $100+ per month for basic access
- LinkedIn: Free tier available, paid for marketing features
- Third-party aggregation services: $50-500+ per month

**Development Time Estimate:**
- **Phase 1** (Basic implementation): 4-6 weeks
- **Phase 2** (Production-ready with all platforms): 8-12 weeks
- **Phase 3** (Advanced features & optimization): 4-6 weeks

**Maintenance Overhead:**
- Regular API version updates and migrations
- Platform policy compliance monitoring
- Token refresh and error handling maintenance
- Performance optimization and scaling

## Key Challenges and Analysis (WordPress Integration)

### Current Blog System Assessment

The RRSS-planner now has a complete WordPress-compatible blog posts system:

**✅ Existing WordPress-Ready Features:**
- Complete blog posts CRUD with WordPress-compatible database structure
- TinyMCE editor matching WordPress editing experience
- Categories and tags system with relationship tables
- WordPress-compatible post states: `draft`, `scheduled`, `publish`
- Slug generation and management (URL-friendly)
- Featured image upload and management
- Excerpt/meta description support
- Multi-business line isolation (each line manages its own content)

**✅ Database Structure Compatibility:**
- `blog_posts` table designed with WordPress `wp_posts` structure in mind
- `wp_post_id` field ready for future synchronization
- Categories and tags tables mirror WordPress `wp_terms` structure
- Proper relationship tables for many-to-many associations

### WordPress Integration Complexity Analysis

**COMPLEXITY RATING: MEDIUM-HIGH (🟡)**

The WordPress integration is significantly simpler than social media publishing due to:
- WordPress REST API is mature, stable, and well-documented
- No complex OAuth flows (can use Application Passwords or JWT)
- No rate limiting concerns for reasonable usage
- Excellent content format compatibility

### Technical Implementation Requirements

**1. WordPress REST API Integration (MEDIUM COMPLEXITY)**
- **API Endpoint:** `/wp-json/wp/v2/posts` for post creation/updates
- **Authentication Options:**
  - Application Passwords (WordPress 5.6+) - RECOMMENDED
  - JWT Authentication plugin
  - Basic Auth (development only)
- **Required Permissions:** `edit_posts`, `publish_posts`, `upload_files`

**2. Per-Business Line WordPress Configuration (MEDIUM COMPLEXITY)**
- **Database Schema Addition:**
```sql
-- WordPress connection settings per business line
ALTER TABLE lineas_negocio ADD COLUMN (
  wordpress_url VARCHAR(255) NULL,
  wordpress_username VARCHAR(100) NULL,
  wordpress_app_password VARCHAR(255) NULL,
  wordpress_enabled BOOLEAN DEFAULT FALSE,
  wordpress_last_sync TIMESTAMP NULL
);
```

**3. Content Mapping & Synchronization (MEDIUM COMPLEXITY)**
- **Field Mapping:**
  - `titulo` → `title.rendered`
  - `contenido` → `content.rendered` (TinyMCE HTML compatible)
  - `excerpt` → `excerpt.rendered`
  - `slug` → `slug`
  - `estado` → `status` (`draft`/`publish`/`future`)
  - `fecha_publicacion` → `date` (for scheduled posts)
  - `imagen_destacada` → `featured_media` (requires media upload)

**4. Media Upload Handling (MEDIUM-HIGH COMPLEXITY)**
- **Challenge:** Featured images need to be uploaded to WordPress media library
- **Process:**
  1. Upload image to WordPress via `/wp-json/wp/v2/media`
  2. Get media ID from response
  3. Assign media ID to post's `featured_media` field
- **Considerations:** Image format compatibility, file size limits

**5. Category & Tag Synchronization (MEDIUM COMPLEXITY)**
- **WordPress Endpoints:**
  - Categories: `/wp-json/wp/v2/categories`
  - Tags: `/wp-json/wp/v2/tags`
- **Strategy Options:**
  1. **Auto-create:** Create categories/tags in WordPress if they don't exist
  2. **Manual mapping:** Admin pre-configures category/tag mappings
  3. **Sync both ways:** Keep categories/tags synchronized between systems

### User Requirements Confirmed ✅

**WordPress Sites Configuration:**
- **Ebone:** https://ebone.es/
- **CUBOFIT:** https://www.cubofit.es/
- **Uniges:** https://uniges3.net/
- **CIDE:** https://ebone.es/catedra/
- **Teia:** No WordPress site (skip blog functionality)

**Technical Details:**
- ✅ Admin access available on all sites
- ✅ WordPress 6.8+ (Application Passwords supported)
- ✅ Can create users and Application Passwords

**Publishing Behavior:**
- ✅ Manual "Publish to WordPress" button
- ✅ Auto-change status to "Published" after successful sync
- ✅ Direct publication (not draft)
- ✅ Upload images to WordPress media library
- ✅ Auto-create categories/tags if they don't exist
- ✅ Map existing WordPress categories/tags when possible

### Implementation Phases

**Phase 1: WordPress Connection Setup (1-2 weeks)**
- Add WordPress configuration to `lineas_negocio` table
- Create WordPress connection testing functionality
- Build admin interface for WordPress settings per business line

**Phase 2: Basic Post Publishing (1-2 weeks)**
- Implement WordPress REST API client
- Create post publishing functionality (title, content, excerpt, status)
- Add "Publish to WordPress" button to blog form

**Phase 3: Advanced Features (1-2 weeks)**
- Featured image upload to WordPress
- Category and tag synchronization
- Scheduled post publishing
- Error handling and retry mechanisms

**Phase 4: Management & Monitoring (1 week)**
- WordPress sync status tracking
- Bulk sync capabilities
- Sync logs and error reporting

**ESTIMATED TOTAL TIME:** 4-7 weeks (significantly less than social media integration)

### Advantages of WordPress Integration

**✅ Benefits:**
- **Mature API:** WordPress REST API is stable and well-documented
- **Content Compatibility:** TinyMCE content transfers seamlessly
- **No Rate Limits:** Reasonable usage won't hit API limits
- **Simple Authentication:** Application Passwords are straightforward
- **SEO Benefits:** Content appears on actual business websites
- **Backup Strategy:** WordPress serves as content backup
- **Professional Presentation:** Content appears on branded business sites

## High-level Task Breakdown

1.  **Task 1: In-depth Analysis of Current Pages and Functionality.** (COMPLETED)
    *   Action: Review each existing "line of business" page. Document all functionalities, UI components, data requirements, and specific business logic for each.
    *   Success Criteria: A comprehensive document or set of notes detailing the features and data points of each current line of business page. Identification of common vs. unique elements.
    *   *Findings: Files like `cubofit.php`, `teia.php` are nearly identical. Core functionalities include publication listing, sorting (date, status), filtering (social network), creation link, share, and feedback count. Key varying elements are `lineaId`, `lineaNombre`, logo path, and active nav link.*
2.  **Task 2: Design Data Model for "Lines of Business".** (COMPLETED)
    *   Action: Define a schema for "lines of business". The existing `lineas_negocio` table will be used and augmented.
    *   Success Criteria: A clearly defined data model/schema for lines of business, approved by the user.
    *   *Decision: The `lineas_negocio` table (which currently has `id` and `nombre`) will be extended by adding a `logo_filename` (VARCHAR(255), NULLABLE) column to store the specific logo file for each line of business (e.g., "logo-cubofit.png"). The application will prepend the base path "assets/images/logos/". Other related tables (`linea_negocio_red_social`, `publicaciones`) are suitable as is.*
3.  **Task 3: Design the Unified Page Architecture & UI/UX.** (COMPLETED)
    *   Action:
        1.  Finalized the name and URL structure for the unified page:
            *   Filename: `planner.php`.
            *   URL: Will use a slug parameter, e.g., `planner.php?slug=cubofit`. (User-friendly URLs like `/planner/cubofit` will be a future enhancement via URL rewriting).
            *   This requires adding a `slug` (VARCHAR, unique) column to the `lineas_negocio` table.
        2.  Designed dynamic main navigation (`nav-simple`):
            *   Populated from `lineas_negocio` (fetching `id`, `nombre`, `slug`).
            *   Links point to `planner.php?slug={slug}`.
            *   Active state based on the current page's slug.
        3.  Planned "Lööp" application branding:
            *   App Name: Lööp.
            *   Logo: `Lööp Logo.png` (path to be confirmed, e.g., `assets/images/brand/loop_logo.png`).
            *   Favicon: Update to Lööp logo.
            *   Page Titles: `Lööp - [Nombre Línea]` (e.g., "Lööp - CUBOFIT"), `Lööp - Dashboard`.
            *   Global Logo Placement: Lööp logo on the far left of the `nav-simple` bar.
        4.  Outlined UI for creating new lines of business (in Spanish):
            *   An admin-accessible button (e.g., "Nueva Línea de Negocio").
            *   Opens a modal with fields for "Nombre de la línea", "Archivo del logo" (filename), and "Slug".
            *   "Guardar" button to submit.
    *   Success Criteria: Documented architectural plan covering the unified page, dynamic navigation, Lööp branding integration, and a concept for line of business management. User approval of UI/UX decisions.
    *   *Decisions made based on user feedback regarding Lööp branding, URL structure, and line of business creation UI.*
4.  **Task 4: Backend Implementation for Managing Lines of Business.** (COMPLETED)
    *   Action:
        1.  **Modified `lineas_negocio` table:** Added `logo_filename` (VARCHAR) and `slug` (VARCHAR, UNIQUE) columns. User confirmed population of these columns for existing data.
        2.  **Developed backend script `crear_linea_negocio.php`:** This script handles the creation of new lines of business. It requires authentication, validates input (nombre, logo_filename, unique slug), inserts into the database, and returns a JSON response.
    *   Success Criteria: The `lineas_negocio` table in the database is updated with the new columns. Backend logic for creating new lines of business is functional.
    *   *Database schema updated by user. `crear_linea_negocio.php` script created.*
5.  **Task 5: Frontend Implementation - "Create New Line of Business" UI.** (COMPLETED)
    *   Action: Develop the admin button and modal UI (as designed in Task 3) for creating lines of business. Implement JavaScript to show/hide the modal and to submit the form data to `crear_linea_negocio.php`. Display success/error messages. JS moved to `assets/js/main.js`, linked with `defer`. Inline `style="display: none;"` removed from modal HTML.
    *   Success Criteria: Admin users can open the modal, fill in and submit details for a new line of business. Successful creation is confirmed.
6.  **Task 6: Unified Page (`planner.php`) - UI/UX Verification and Finalization.**
    *   `planner.php` is substantially developed and functional. This task focuses on verifying specific UI/UX elements and ensuring full feature parity with old pages.
    *   Actions for Verification:
        1.  **Share Button:** Confirm the "Compartir Vista" button functionality for the current line of business.
        2.  **Feedback Count:** Ensure feedback counts for publications are correctly displayed.
        3.  **"Nueva Publicación" Link:** Verify the link passes correct `linea_id` and `linea_slug` to `publicacion_form.php`, and the form loads correctly.
        4.  **"Mostrar Publicados" Toggle:** Confirm the toggle switch works correctly, filters publications by status, and any `data-estado` attributes are handled appropriately for persistent filtering if applicable.
        5.  **Feedback Display Modal (`feedbackDisplayModal`):** Verify that clicking the feedback count opens the modal and displays the correct comments for the selected publication.
        6.  **Image Preview Modal (`imageModal`):** Confirm that clicking publication image thumbnails opens a larger preview in the `imageModal`.
        7.  **Overall UI/UX Consistency:** Compare `planner.php` (for a specific line of business) against its old static page equivalent (e.g., `cubofit.php`) to ensure visual and behavioral consistency.
        8.  **Error Handling:** Review and test error states (e.g., invalid slug, DB errors) for graceful handling and informative user messages.
    *   Success Criteria: All UI/UX elements on `planner.php` are fully functional and consistent with original page features. All verification actions above are successfully completed.
7.  **Task 7: Implement Dynamic Navigation and "Lööp" Branding.** (COMPLETED)
    *   Action:
        1.  **Dynamic Main Navigation:**
            *   Created `includes/nav.php` with PHP logic to fetch lines of business and generate navigation links dynamically.
            *   Lööp logo (`assets/images/logos/loop-logo.png`) added to the left of the navigation bar.
            *   "Dashboard" and line of business links point to `/index.php` and `/planner.php?slug={slug}` respectively.
            *   Logic implemented in `includes/nav.php` to set the `active` class on the current navigation item.
            *   `index.php` and `planner.php` updated to `require 'includes/nav.php';`.
        2.  **"Lööp" Branding Application:**
            *   Page titles updated in `index.php` to "Lööp - Dashboard".
            *   Page titles updated in `planner.php` to "Lööp - <?php echo htmlspecialchars($current_linea_nombre); ?>".
            *   Site favicon updated in `index.php` and `planner.php` to use `assets/images/logos/Loop-favicon.png`.
    *   Success Criteria: Main navigation is dynamic, incorporates Lööp branding, and correctly indicates active links. Page titles and favicon are updated. The application consistently presents the "Lööp" brand.
8.  **Task 8: Testing and Refinement.**
    *   Action: Conduct thorough testing:
        *   Creating and managing lines of business.
        *   Functionality parity between the old pages and the new unified page for each line of business.
        *   Usability of the new interface/navigation.
    *   Success Criteria: All tests pass. Any bugs identified are fixed. User confirms satisfaction with the new system.
9.  **Task 9: (Optional) Deprecate/Remove Old Pages.**
    *   Action: Once the new system is stable and validated, plan for the removal or redirection of the old individual PHP files (`cubofit.php`, `teia.php`, etc.).
    *   Success Criteria: Old pages are successfully deprecated without disrupting users.
10. **Task 10: UI Improvements and Legacy Cleanup** (COMPLETED)
    *   Action:
        1.  **Delete Legacy Pages (CONFIRMED UNUSED):**
            - Delete the following obsolete files that are no longer used since the unified system is active:
              - `cubofit.php`, `teia.php`, `ebone.php`, `uniges.php`
            - These files are legacy from before the unified `planner.php` system was implemented
            - Navigation now points exclusively to `planner.php?slug={slug}` for business lines
        2.  **"Mostrar publicados" Toggle Default State Changes:**
            - Remove `checked` attribute from `<input type="checkbox" id="toggle-published" checked>` in:
              - `planner.php` (line 205) - **ONLY ACTIVE PAGE**
              - `share_view.php` (line 173) - public sharing page
        3.  **Remove Time Display from Publications Table:**
            - Modify the date display format from `date("d/m/Y H:i", strtotime($publicacion['fecha_programada']))` to `date("d/m/Y", strtotime($publicacion['fecha_programada']))` in:
              - `planner.php` (line 225) - **ONLY ACTIVE PAGE**
              - `share_view.php` (if it displays the table) - public sharing page
        4.  **Clean up any remaining references:**
            - Check `publicacion_form.php` for any hardcoded links to old pages
            - Update any remaining references to use the new unified system
        5.  **Test Functionality:**
            - Verify toggle works correctly with new default state in `planner.php`
            - Confirm date display shows only dates without time
            - Test that all business line functionality works through `planner.php`
    *   Success Criteria: 
        - Legacy pages are deleted and no longer accessible
        - The "Mostrar publicados" toggle is unchecked by default on `planner.php` and `share_view.php`
        - Published posts are hidden by default (since toggle is unchecked)
        - Users can still check the toggle to show published posts when needed
        - The publications table shows only dates (format: `dd/mm/YYYY`) without time
        - All business line functionality works exclusively through the unified `planner.php` system
        - No broken links or references to deleted pages remain

## Project Status Board

*   [x] **Task 1: In-depth Analysis of Current Pages and Functionality.**
*   [x] **Task 2: Design Data Model for "Lines of Business".**
*   [x] **Task 3: Design the Unified Page Architecture & UI/UX.**
*   [x] **Task 4: Backend Implementation for Managing Lines of Business.**
*   [x] **Task 5: Frontend Implementation - "Create New Line of Business" UI.**
*   [ ] **Task 6: Unified Page (`planner.php`) - UI/UX Verification and Finalization.**
    *   [ ] 6.1 Verify Share Button
    *   [ ] 6.2 Verify Feedback Count display
    *   [ ] 6.3 Verify "Nueva Publicación" link/form
    *   [ ] 6.4 Verify "Mostrar Publicados" toggle
    *   [ ] 6.5 Verify Feedback Display Modal
    *   [ ] 6.6 Verify Image Preview Modal
    *   [ ] 6.7 Overall UI/UX Consistency Check
    *   [ ] 6.8 Error Handling Review
*   [x] **Task 7: Implement Dynamic Navigation and "Lööp" Branding.**
*   [ ] **Task 8: Testing and Refinement.**
*   [ ] **Task 9: (Optional) Deprecate/Remove Old Pages.**
*   [x] **Task 10: UI Improvements and Legacy Cleanup** (COMPLETED)

## Executor's Feedback or Assistance Requests

*   Executor mode. Task 4 (Backend for Managing Lines of Business) is complete.
*   Task 5 (Frontend "Create New Line of Business" UI) is complete after debugging modal visibility (removed inline style, ensured JS uses classList.toggle, and CSS supports .modal.show).
*   `planner.php` is significantly developed. User confirmed it's working well. Planner mode initiated to refine Task 6/7 based on user's specific "Next Steps" list.
*   Task 7 (Dynamic Nav & Branding) is complete. Header titles in `index.php` and `planner.php` were removed to reduce redundancy. `includes/header.php` was deleted as it was unused by these main pages.
*   A critical bug in `planner.php` where social media filters were not correctly scoped to the current line of business has been fixed by adjusting the SQL parameter binding order. User confirmed the fix.
*   User happy with current progress and has decided to pause work for now.
*   **Task 10 COMPLETED (Executor mode):**
    - ✅ **Legacy Pages Deleted**: `cubofit.php`, `ebone.php`, `teia.php`, `uniges.php` successfully removed
    - ✅ **Toggle Default Changed**: "Mostrar publicados" toggle now unchecked by default in `planner.php` and `share_view.php`
    - ✅ **Date Format Updated**: Publications table now shows only dates (`d/m/Y`) without time in `planner.php`
    - ✅ **References Cleaned**: Hardcoded navigation in `publicacion_form.php` replaced with dynamic navigation
*   **BLOG FORM ENHANCEMENT COMPLETED (Executor mode):**
    - ✅ **WordPress-Ready Features Added**: `blog_form.php` now includes all WordPress-compatible features
    - ✅ **TinyMCE Integration**: Full WYSIWYG editor with WordPress-like functionality, Spanish language support
    - ✅ **Categories & Tags System**: Complete checkbox interface for selecting multiple categories and tags
    - ✅ **Slug Management**: Auto-generation from title with manual override, URL-friendly validation
    - ✅ **Enhanced Database Operations**: Full CRUD with category/tag relationships, proper validation
    - ✅ **Responsive Design**: Mobile-friendly layout with proper CSS styling
    - ✅ **Form Validation**: Frontend and backend validation for all fields including slug format
    - ✅ **User Experience**: Auto-slug generation, real-time validation, form state preservation on errors
    - ✅ **Task 9 Accomplished**: Legacy page deprecation completed as part of Task 10

### WordPress Integration Implementation

#### Phase 1: Database Setup ✅ COMPLETED
- [x] Create WordPress configuration fields for `lineas_negocio` table
- [x] Add WordPress sync fields to `blog_posts` table  
- [x] Pre-populate WordPress URLs for existing business lines
- [x] Create database migration script

#### Phase 2: WordPress API Integration ✅ COMPLETED
- [x] Create `WordPressAPI.php` class with full REST API functionality
- [x] Implement connection testing
- [x] Add post publishing with categories/tags
- [x] Handle featured image upload
- [x] Add error handling and retry mechanisms

#### Phase 3: Publishing Interface ✅ COMPLETED
- [x] Create `publish_to_wordpress.php` endpoint
- [x] Update `blog_form.php` with WordPress publish button
- [x] Modify `planner.php` table with WordPress column and actions
- [x] Add CSS styling for WordPress buttons
- [x] Implement JavaScript publishing functions

#### Phase 4: Administrative Interface ✅ COMPLETED
- [x] Create `wordpress_config.php` admin interface
- [x] Add per-business-line WordPress configuration
- [x] Implement connection testing in admin
- [x] Add visual status indicators
- [x] Update navigation with WordPress config link

#### Phase 5: WordPress Categories & Tags Integration ✅ COMPLETED
- [x] Add `getCategories()` and `getTags()` methods to WordPressAPI class
- [x] Create `get_wordpress_taxonomies.php` endpoint for dynamic loading
- [x] Update `blog_form.php` with WordPress taxonomies section
- [x] Add dynamic loading of WordPress categories and tags via JavaScript
- [x] Update CSS styling for WordPress taxonomies display
- [x] Modify publishing functions to send selected WordPress categories/tags
- [x] Update `publish_to_wordpress.php` to handle WordPress taxonomies by ID
- [x] Enhance WordPressAPI to prioritize WordPress taxonomies over local ones

### Current Status: WordPress Integration Complete ✅
- ✅ All core WordPress publishing functionality implemented
- ✅ Database schema updated with WordPress fields  
- ✅ API integration class completed with taxonomies support
- ✅ Admin configuration interface ready
- ✅ Dynamic WordPress categories and tags loading implemented
- ✅ Blog form enhanced with WordPress taxonomies selection
- ✅ **FIXED:** Categories bug - WordPress categories now publish correctly from table
- ✅ **FIXED:** Double confirmation dialog bug for blog post deletion

### Recent Fixes Applied:
- **WordPress Categories Fix:** Added fallback to retrieve saved wp_categories_selected from database when publishing from table
- **UI Fix:** Eliminated double confirmation dialogs for blog post deletion by excluding blog posts from generic delete confirmation

## Executor's Feedback or Assistance Requests

**TASK COMPLETED SUCCESSFULLY ✅**

**WordPress Categories Bug Resolution:**
- **Root Cause Identified:** Missing fallback for saved WordPress categories when publishing from table
- **Fix Applied:** Added database lookup for wp_categories_selected in publish_to_wordpress.php
- **Testing:** Confirmed working - posts now publish with correct categories
- **Code Cleanup:** Removed debug logging after successful fix

**Double Confirmation Bug Resolution:**  
- **Root Cause Identified:** Generic delete event listener conflicting with specific blog post deletion
- **Fix Applied:** Modified generic listener to exclude blog post delete buttons
- **Testing:** Confirmed working - only single confirmation dialog appears

**Status:** Both issues resolved and tested successfully. WordPress integration now fully functional.

## Lessons

*   The current "line of business" pages (e.g., `cubofit.php`, `teia.php`) are highly redundant, differing primarily by hardcoded `lineaId`, `lineaNombre`, logo path, and active navigation state. This makes them excellent candidates for unification into a single dynamic template.
*   The `lineas_negocio` table will be extended with a `logo_filename` column (VARCHAR(255), NULLABLE) to store logo filenames.
*   The unified page will be `planner.php` and use a URL parameter `slug` (e.g., `planner.php?slug=cubofit`).
*   App branding will be "Lööp" with its logo used for favicon and in navigation. Page titles: `Lööp - [Specific Name]`.
*   New lines of business will be created via an admin modal (UI in Spanish).
*   `crear_linea_negocio.php` script created to handle backend logic for adding new business lines, returning JSON.

## High-level Task Breakdown

### SOCIAL MEDIA PUBLISHING IMPLEMENTATION PLAN

Based on the complexity analysis, here's the recommended phased approach:

#### **PHASE 1: Foundation & Instagram Integration (4-6 weeks)**

**Task 1.1: Database Schema Extension (Priority: HIGH)**
- Success Criteria: All new database tables created and tested
- Deliverables:
  - Create `social_media_tokens` table with encryption
  - Create `publication_queue` table for job management
  - Create `platform_configs` table for app settings
  - Implement database migrations and rollback scripts
- Time Estimate: 3-5 days

**Task 1.2: OAuth Infrastructure Development (Priority: HIGH)**
- Success Criteria: Secure token storage and refresh system working
- Deliverables:
  - OAuth 2.0 authentication flow implementation
  - Token encryption/decryption utilities
  - Automatic token refresh background process
  - Error handling for expired/revoked tokens
- Time Estimate: 7-10 days

**Task 1.3: Instagram Graph API Integration (Priority: HIGH)**
- Success Criteria: Can successfully post to Instagram Business accounts
- Deliverables:
  - Facebook Developer App setup and configuration
  - Instagram Graph API wrapper class
  - Two-step publishing implementation (container → publish)
  - Basic error handling and retry logic
- Time Estimate: 10-14 days

**Task 1.4: Basic Queue System (Priority: MEDIUM)**
- Success Criteria: Scheduled posts can be queued and processed
- Deliverables:
  - Simple cron job for processing publication queue
  - Basic rate limiting implementation for Instagram
  - Job status tracking and logging
- Time Estimate: 5-7 days

#### **PHASE 2: Multi-Platform Support (6-8 weeks)**

**Task 2.1: Facebook Page Publishing (Priority: MEDIUM)**
- Success Criteria: Can publish to Facebook Pages via Graph API
- Deliverables:
  - Facebook Page integration using existing OAuth infrastructure
  - Content format adaptation for Facebook
  - Cross-posting capability (Instagram + Facebook)
- Time Estimate: 7-10 days

**Task 2.2: Twitter/X API Integration (Priority: MEDIUM)**
- Success Criteria: Can publish to Twitter accounts
- Deliverables:
  - Twitter Developer account setup and API access
  - Twitter API v2 implementation
  - Character limit handling and thread support
  - Cost evaluation and tier selection
- Time Estimate: 10-14 days

**Task 2.3: LinkedIn API Integration (Priority: LOW)**
- Success Criteria: Can publish to LinkedIn company pages
- Deliverables:
  - LinkedIn Developer program enrollment
  - LinkedIn Share API implementation
  - Business account linkage and verification
- Time Estimate: 7-10 days

**Task 2.4: Enhanced Queue Management (Priority: HIGH)**
- Success Criteria: Robust job queue with advanced features
- Deliverables:
  - Platform-specific rate limiting
  - Intelligent retry logic with exponential backoff
  - Priority queuing for time-sensitive posts
  - Monitoring and alerting system
- Time Estimate: 10-14 days

#### **PHASE 3: Production Readiness (4-6 weeks)**

**Task 3.1: Webhook Infrastructure (Priority: MEDIUM)**
- Success Criteria: Real-time status updates from platforms
- Deliverables:
  - HTTPS webhook endpoint
  - Signature verification for security
  - Status update processing and UI notifications
- Time Estimate: 7-10 days

**Task 3.2: Advanced Error Handling (Priority: HIGH)**
- Success Criteria: Comprehensive error management and recovery
- Deliverables:
  - Detailed error logging and reporting
  - User notification system for failed posts
  - Manual retry capabilities
  - Graceful degradation when platforms are unavailable
- Time Estimate: 7-10 days

**Task 3.3: Content Format Optimization (Priority: MEDIUM)**
- Success Criteria: Content automatically optimized for each platform
- Deliverables:
  - Image format conversion utilities
  - Aspect ratio adjustment algorithms
  - Text truncation and adaptation logic
  - Platform-specific hashtag formatting
- Time Estimate: 5-7 days

**Task 3.4: Security & Compliance Hardening (Priority: HIGH)**
- Success Criteria: Production-ready security implementation
- Deliverables:
  - GDPR compliance implementation
  - Data retention and deletion policies
  - API key rotation system
  - Security audit and penetration testing
- Time Estimate: 7-10 days

#### **PHASE 4: Analytics & Optimization (2-4 weeks)**

**Task 4.1: Publishing Analytics (Priority: LOW)**
- Success Criteria: Comprehensive analytics dashboard
- Deliverables:
  - Success rate tracking per platform
  - Performance metrics and KPIs
  - Error analysis and trends
  - Cost optimization recommendations
- Time Estimate: 7-10 days

**Task 4.2: User Experience Enhancements (Priority: LOW)**
- Success Criteria: Improved UI/UX for social media management
- Deliverables:
  - Social media account management interface
  - Real-time publishing status indicators
  - Preview functionality for different platforms
  - Bulk posting capabilities
- Time Estimate: 7-10 days

### COMPLEXITY ASSESSMENT SUMMARY

**OVERALL COMPLEXITY: HIGH (🔴)**

**Key Risk Factors:**
1. **Platform Dependencies:** Heavy reliance on external APIs with changing policies
2. **Technical Complexity:** Multiple authentication flows, rate limiting, and content adaptation
3. **Maintenance Overhead:** Ongoing API updates, token management, and error handling
4. **Cost Implications:** Paid API access for Twitter/X, potential high-volume charges

**Estimated Total Development Time:** 16-24 weeks (4-6 months)

**Estimated Total Cost:**
- Development: €15,000 - €25,000 (assuming €50-75/hour)
- Monthly API costs: €150 - €500/month
- Infrastructure costs: €50 - €200/month

### RECOMMENDATIONS

#### **Option 1: Full Custom Implementation (NOT RECOMMENDED)**
- **Pros:** Complete control, no third-party dependencies
- **Cons:** Very high complexity, ongoing maintenance burden, significant cost
- **Timeline:** 4-6 months development + ongoing maintenance
- **Cost:** €20,000+ development + €200-700/month operational

#### **Option 2: Third-Party Integration Service (RECOMMENDED)**
- **Pros:** Faster implementation, managed maintenance, proven reliability
- **Cons:** Monthly costs, less customization, vendor lock-in
- **Examples:** Hootsuite API, Buffer API, Later API, Sprout Social API
- **Timeline:** 2-4 weeks integration
- **Cost:** €50-300/month per business line

#### **Option 3: Hybrid Approach (PARTIALLY RECOMMENDED)**
- **Pros:** Balance of control and efficiency
- **Cons:** Still complex, requires careful vendor selection
- **Approach:** Use third-party for complex platforms (Instagram), direct API for simpler ones (Twitter)
- **Timeline:** 6-10 weeks development
- **Cost:** €8,000-15,000 development + €100-400/month operational

#### **Option 4: Gradual Implementation (RECOMMENDED FOR CURRENT SYSTEM)**
- **Pros:** Manageable scope, iterative improvement, lower risk
- **Cons:** Longer timeline to full functionality
- **Approach:** Start with Instagram only, add platforms gradually
- **Timeline:** 4-6 weeks for Instagram, additional 2-3 weeks per platform
- **Cost:** €5,000-10,000 per platform + ongoing API costs

### FINAL RECOMMENDATION

Given the current system's maturity and the complexity of social media APIs, I recommend **Option 4: Gradual Implementation** starting with Instagram Graph API only.

**Justification:**
1. **Risk Management:** Allows learning and iteration without overwhelming scope
2. **Cost Control:** Spreads development costs over time
3. **User Validation:** Can validate user demand before full investment
4. **Technical Learning:** Team gains expertise with each platform
5. **Flexibility:** Can pivot to third-party solutions if development proves too complex

**Next Steps if Approved:**
1. Set up Facebook Developer account and Instagram Business verification
2. Begin Phase 1 database schema design
3. Create proof-of-concept for Instagram posting
4. Evaluate user response and business value before expanding

## Project Status Board

- [x] Set "Mostrar publicados" toggle to unchecked by default
- [x] Remove time display from "Publicaciones Programadas y Pasadas" table
- [x] Create unified business line management system
- [x] Implement dynamic page structure
- [x] Create business line creation modal and backend
- [ ] **NUEVA PRIORIDAD:** Implementar Interfaz con Selector de Línea de Negocio (Opción 1)
  - [x] Fase 1: Estructura Base (1-2 semanas)
    - [x] Dropdown de líneas de negocio con theming y logos
    - [x] Sistema de tabs con persistencia en URL
    - [x] Nuevo header integrado
  - [ ] Fase 2: Selector de Redes (1 semana)
    - [ ] Iconos circulares para redes sociales
    - [ ] Carga dinámica de redes por línea
  - [ ] Fase 3: Adaptación de Contenido (1 semana)
    - [ ] Routing por tipo de contenido
    - [ ] Optimización móvil
  - [ ] Fase 4: Integración Final (3-5 días)
    - [ ] Theming dinámico por línea
    - [ ] Testing y pulido

## Current Status / Progress Tracking

**Última Actualización:** 30 de Diciembre, 2024

**Estado Actual:** Fase 1 COMPLETADA (Incluyendo Correcciones) - Interfaz completamente integrada

**Tareas Completadas Recientemente:**
- ✅ **Dropdown de líneas de negocio** con theming dinámico y logos integrados
- ✅ **Sistema de tabs** para tipos de contenido (Posts Sociales / Blog Posts)
- ✅ **Nuevo header mejorado** con navegación intuitiva
- ✅ **URL routing** con parámetros slug y type
- ✅ **Theming dinámico** por línea de negocio
- ✅ **JavaScript interactivo** para dropdown con navegación por teclado
- ✅ **Responsive design** optimizado para móviles
- ✅ **Sección placeholder** para Blog Posts con mensaje "coming soon"

**Correcciones de Integración Completadas:**
- ✅ **Eliminado header antiguo** (`includes/nav.php`) que se duplicaba
- ✅ **Corregido espaciado y layout**:
  - Actualizado contenedor `.app-simple` con styling apropiado
  - Eliminado box-shadow duplicado del header
  - Añadido margen correcto a secciones de contenido (20px)
  - Header integrado limpiamente dentro del contenedor de la app
- ✅ **Optimizado botón "Compartir Vista"**:
  - Movido a la misma línea que las pestañas de contenido
  - Ahorra espacio vertical y mejora la ergonomía
  - Responsive design actualizado para móviles
- ✅ **Añadido logo Loop**:
  - Integrado logo de marca (default.png) en el nuevo header
  - Mantiene la identidad visual de Loop
  - Responsive con diferentes tamaños según dispositivo
  - Efecto hover sutil para interactividad

**Funcionalidades Implementadas:**
1. **Dropdown Business Line Selector**:
   - Muestra todas las líneas disponibles con logos
   - Navegación rápida entre líneas sin perder contexto
   - Theming dinámico según la línea actual
   - Accesibilidad completa (navegación por teclado)

2. **Content Type Tabs**:
   - Tab "Posts Sociales" completamente funcional
   - Tab "Blog Posts" preparado para implementación futura
   - Persistencia de tipo de contenido en URL
   - Integración con filtros y ordenación existentes

3. **Enhanced Header**:
   - Botón "Dashboard" para navegación de regreso
   - Diseño limpio y profesional
   - Responsive para dispositivos móviles
   - Integración con botón "Compartir Vista" existente

**MODO PLANNER ACTIVADO - Análisis Blog Posts:**

**Próximos Pasos - Funcionalidad Blog Posts:**
1. **Análisis de Diferencias** entre Social Posts vs Blog Posts
2. **Propuesta de Arquitectura** para contenido mixto
3. **Plan de Implementación** paso a paso con UX optimizado

**Logros Técnicos:**
- ✅ Backward compatibility mantenida (URLs antiguas siguen funcionando)
- ✅ No regresiones en funcionalidad existente
- ✅ Código modular y escalable para futuras mejoras
- ✅ CSS variables utilizadas para theming consistente

## PLANNER ANALYSIS - Blog Posts Functionality

### 📊 **Análisis de Diferencias: Social Posts vs Blog Posts**

**Social Posts (Actual):**
- Contenido: Texto corto (200-300 caracteres)
- Imagen: Una imagen social (cuadrada/rectangular)
- Plataformas: Múltiples redes sociales simultáneas
- Metadatos: Fecha, estado, redes seleccionadas
- Objetivo: Engagement rápido, viral

**Blog Posts (Requerido):**
- Contenido: Texto largo (800-5000+ palabras)
- Imagen: Imagen destacada + múltiples imágenes en contenido
- Plataforma: Una sola (blog propio)
- Metadatos: Título, excerpt, categorías, tags, SEO, slug URL
- Objetivo: Contenido de valor, SEO, autoridad

### 🏗️ **Propuesta de Arquitectura (ACTUALIZADA)**

**🔄 CAMBIO IMPORTANTE: Posts para WordPress**
- Usuario confirma: Posts serán para WordPress
- Objetivo futuro: Publicación directa desde plataforma
- Esto requiere compatibilidad con WordPress REST API

**Opción A: Tabla Unificada** 
- Mantener tabla `publicaciones` actual
- Añadir campos: `tipo_contenido`, `titulo`, `excerpt`, `categorias`, `tags`, `slug_url`
- Ventajas: Simplicidad, aprovecha código existente
- Desventajas: Algunos campos quedarán vacíos, menos flexible para WordPress

**Opción B: Tablas Separadas (NUEVA RECOMENDACIÓN)**
- Nueva tabla `blog_posts` independiente con estructura WordPress-compatible
- Ventajas: Estructura limpia, preparada para WordPress API, más libertad
- Desventajas: Duplicación de código inicial (pero más mantenible a largo plazo)

### 🎨 **Plan UX/UI Optimizado**

**1. Formulario Adaptativo**
- Mismo archivo `publicacion_form.php` con lógica condicional
- Campos dinámicos según `?type=social` o `?type=blog`
- Social: Contenido corto + Redes sociales
- Blog: Título + Contenido largo + Categorías + SEO

**2. Vista de Lista Diferenciada**
- Social: Tabla actual (Fecha, Imagen, Contenido, Redes)
- Blog: Tabla adaptada (Fecha, Título, Excerpt, Categorías, Estado)
- Filtros específicos por tipo de contenido

**3. Componentes Reutilizables**
- Editor de texto enriquecido para blog posts (TinyMCE - compatible WordPress)
- Selector de categorías/tags
- Preview de contenido adaptado por tipo

### 🔌 **Consideraciones WordPress Integration**

**Estructura de Base de Datos Compatible:**
- `blog_posts` → Similar a `wp_posts` (id, post_title, post_content, post_excerpt, post_name/slug)
- `blog_categorias` → Similar a `wp_terms` (term_id, name, slug)
- Estados compatibles: 'draft', 'publish', 'scheduled' (mismo que WordPress)

**Campos Adicionales para WordPress:**
- `slug` (URL-friendly, auto-generado desde título)
- `meta_description` (SEO)
- `imagen_destacada` (featured image path)
- `wp_post_id` (para sincronización futura)

**Preparación para WordPress REST API:**
- Estructura JSON-compatible
- Campos mapeables directamente a WordPress
- Sistema de autenticación preparado para OAuth

### 📋 **Plan de Implementación WordPress-Ready (Paso a Paso)**

**FASE 2A: Base de Datos WordPress-Compatible (1-2 días)** ✅ COMPLETADA
- [x] Crear tabla `blog_posts` con estructura similar a wp_posts
- [x] Campos: `id`, `titulo`, `contenido`, `excerpt`, `slug`, `fecha_creacion`, `fecha_publicacion`, `estado`, `imagen_destacada`, `linea_negocio_id`
- [x] Crear tabla `blog_categorias` (compatible con wp_terms)
- [x] Crear tabla `blog_tags` (compatible con wp_terms)
- [x] Tablas de relación: `blog_post_categoria`, `blog_post_tag`
- [x] Script de migración creado (`database_migration_blog_posts.sql`)
- [x] Runner PHP para ejecutar migración (`run_migration.php`)
- [x] Datos de ejemplo: categorías y tags de muestra (cada línea gestiona las suyas)
- [x] Corregida lógica: posts van a webs específicas (no duplicar categorías por línea)

**FASE 2B: Backend Logic WordPress-Ready (2-3 días)** ✅ COMPLETADA
- [x] Crear `blog_form.php` independiente (WordPress-compatible)
- [x] Funciones específicas para blog posts implementadas
- [x] Validaciones específicas para contenido largo
- [x] Sistema de subida de imágenes específico para blog (`uploads/blog/{linea_id}/`)
- [x] Estructura preparada para futura integración WordPress API

**FASE 2C: Frontend UX Optimizado (3-4 días)** ✅ COMPLETADA
- [x] Formulario completo para blog posts (título, contenido, excerpt)
- [x] Vista de tabla específica para blog posts con ordenación
- [x] Sistema de toggle para mostrar/ocultar publicados
- [x] Funcionalidad completa de CRUD (crear, leer, actualizar, eliminar)
- [x] Estados WordPress-compatibles (draft, scheduled, published)
- [x] Integración completa con la interfaz existente

**FASE 2D: Testing & WordPress Preparation (1-2 días)**
- [ ] Testing completo de funcionalidad blog
- [ ] Verificar compatibilidad de estructura con WordPress
- [ ] Documentar campos para futura integración API
- [ ] Responsive design verification

## Executor's Feedback or Assistance Requests

**Estado:** EXECUTOR MODE - WordPress Integration Phase 1 COMPLETADA

**Progreso Actual:**
✅ **FASE 1: WordPress Connection Setup (COMPLETADA)**
- Base de datos actualizada con campos de configuración WordPress
- Clase WordPressAPI creada con funcionalidad completa REST API
- Interface de configuración administrativa implementada
- Migración de base de datos preparada

✅ **FASE 2: Basic Post Publishing (COMPLETADA)**
- Endpoint `publish_to_wordpress.php` implementado
- Botón "Publicar en WordPress" añadido al formulario de blog
- Botón WordPress añadido a la tabla de blog posts
- JavaScript para publicación implementado
- Estados de sincronización WordPress añadidos a la tabla
- Navegación actualizada con enlace a configuración WordPress

**Archivos Creados/Modificados:**
- ✅ `database_migration_wordpress.sql` - Migración de base de datos
- ✅ `run_wordpress_migration.php` - Runner para migración
- ✅ `includes/WordPressAPI.php` - Clase para integración REST API
- ✅ `publish_to_wordpress.php` - Endpoint de publicación
- ✅ `wordpress_config.php` - Interface administrativa
- ✅ `blog_form.php` - Añadido botón WordPress
- ✅ `planner.php` - Tabla actualizada con columna WordPress
- ✅ `assets/css/styles.css` - Estilos para botones WordPress
- ✅ `assets/js/main.js` - JavaScript para publicación
- ✅ `includes/nav.php` - Enlace configuración WordPress

**Funcionalidades Implementadas:**
1. **Configuración por Línea de Negocio**: Cada línea puede configurar sus credenciales WordPress independientemente
2. **Test de Conexión**: Validación automática de credenciales al guardar
3. **Publicación Manual**: Botón "Publicar en WordPress" en formulario y tabla
4. **Estado de Sincronización**: Indicadores visuales del estado de publicación
5. **Manejo de Categorías/Tags**: Auto-creación en WordPress si no existen
6. **Subida de Imágenes**: Upload automático de imagen destacada a WordPress
7. **Mapeo de Estados**: draft/scheduled/publish compatible con WordPress
8. **Gestión de Errores**: Logging y notificaciones de errores de sincronización

**Próximo Paso:** Ejecutar migración de base de datos y configurar credenciales WordPress para testing

---

**Actualización Executor - 30 Diciembre 2024 (Fase 2B Completada):**

**Archivos Creados/Modificados:**
- ✅ `blog_form.php` - Formulario completo para blog posts con funcionalidad crear/editar
- ✅ `assets/css/styles.css` - Estilos específicos para formulario de blog

**Funcionalidades Implementadas:**
1. **Formulario Blog Posts Completo:**
   - Autenticación y validación de línea de negocio
   - Campos: título, contenido, excerpt, imagen destacada, fecha, estado
   - Procesamiento POST con validación de datos
   - Manejo de imágenes con directorio separado (`uploads/blog/{linea_id}/`)
   - Estados WordPress-compatibles: draft, scheduled, published

2. **Estructura WordPress-Ready:**
   - Campos de base de datos mapeables a WordPress
   - Estructura JSON-compatible para futura API integration
   - Validaciones apropiadas para contenido largo

3. **CSS Styling:**
   - Estilos específicos para formulario blog (.blog-form)
   - Textarea expandido para contenido largo (400px min-height)
   - Sistema de alertas para errores de validación
   - Badges para mostrar línea de negocio

**Próximo Paso:** Crear la vista de listado de blog posts y sistema de gestión para completar la Fase 2C.

**Pregunta para Usuario:** ¿Proceder con la creación de la tabla de blog posts (equivalente a la tabla de publicaciones sociales) o hay algún ajuste específico que necesitas en el formulario?

---

**Actualización Executor - 30 Diciembre 2024 (Fase 2C Completada):**

**Archivos Creados/Modificados:**
- ✅ `planner.php` - Añadida funcionalidad completa de blog posts
- ✅ `assets/js/main.js` - JavaScript para toggle, actualización de estado y eliminación de blog posts
- ✅ `assets/css/styles.css` - Estilos específicos para tabla de blog posts
- ✅ `blog_update_estado.php` - Endpoint para actualizar estado de blog posts
- ✅ `blog_delete.php` - Endpoint para eliminar blog posts con limpieza de imágenes

**Funcionalidades Implementadas:**

1. **Vista de Tabla de Blog Posts Completa:**
   - Tabla con columnas: Fecha, Imagen, Título, Excerpt, Estado, Acciones
   - Ordenación por fecha, título y estado
   - Toggle para mostrar/ocultar posts publicados
   - Estados WordPress-compatibles: Borrador, Programado, Publicado
   - Botón "Nuevo Blog Post" funcional

2. **Gestión Completa de Blog Posts:**
   - **Crear**: Formulario completo con validaciones
   - **Leer**: Vista de tabla con información organizada
   - **Actualizar**: Cambio de estado en tiempo real desde la tabla
   - **Eliminar**: Eliminación con confirmación y limpieza de archivos

3. **Integración con Sistema Existente:**
   - Tab "Blog Posts" ahora funcional (eliminada clase "disabled")
   - Misma estructura de navegación que posts sociales
   - Theming dinámico por línea de negocio
   - Responsive design para móviles

4. **JavaScript Interactivo:**
   - Toggle independiente para blog posts (`toggle-published-blog`)
   - Actualización de estado sin recargar página
   - Función global `deleteBlogPost()` con confirmación
   - Filtrado visual en tiempo real

**Características Técnicas:**
- **WordPress-Ready**: Estados y estructura compatibles con WordPress
- **Seguridad**: Validaciones robustas en frontend y backend
- **Performance**: Queries optimizadas con ordenación en base de datos
- **UX**: Interfaz consistente con el resto de la aplicación

**Estado Actual:** Blog Posts completamente funcional y listo para usar. La funcionalidad está al mismo nivel que los posts sociales, con todas las operaciones CRUD implementadas.

**Próximo Paso:** Fase 2D (Testing) - verificar que todo funciona correctamente en diferentes escenarios.

**Detalles de la Implementación:**
- Dropdown funcional con logos y theming por línea
- Tabs de contenido con Posts Sociales activo y Blog Posts preparado
- JavaScript completo con navegación por teclado y accesibilidad
- Responsive design para móviles
- Integración completa con sistema de filtros y ordenación existente

**¿Siguiente paso?** El usuario puede probar la funcionalidad y decidir si:
1. Proceder con Fase 2 (iconos circulares para redes sociales)
2. Hacer ajustes a la implementación actual
3. Considerar la implementación completa y pasar a otras prioridades

## Lessons

### User Specified Lessons
- Include info useful for debugging in the program output.
- Read the file before you try to edit it.
- If there are vulnerabilities that appear in the terminal, run npm audit before proceeding
- Always ask before using the -force git command

### Project-Specific Lessons
- When modifying JavaScript behavior, always test the toggle state changes in browser
- CSS modifications should be tested across different screen sizes
- Database schema changes require careful consideration of existing data

## PENDING PROJECTS

### Social Media Direct Publishing Evaluation (December 30, 2024)

**Status:** Analysis completed - Implementation deferred

**Summary:** Comprehensive evaluation of adding direct social media publishing functionality to RRSS-planner. Analysis shows HIGH complexity due to platform API requirements, authentication flows, and ongoing maintenance needs.

**Key Findings:**
- Instagram: Highest complexity (requires Business accounts, Facebook integration)
- Facebook: High complexity (unified with Instagram via Graph API)
- Twitter/X: Medium-high complexity (paid API access required)
- LinkedIn: Medium complexity (professional focus, stable API)

**Recommended Approach:** Gradual implementation starting with Instagram Graph API only
- Estimated time: 4-6 weeks for Instagram integration
- Estimated cost: €5,000-10,000 + ongoing API costs
- Alternative: Third-party services (€50-300/month per business line)

**Decision:** Deferred for future consideration. Focus on current system improvements first.

**Documentation:** Full analysis available in previous sections of this document for future reference.

## Key Challenges and Analysis (UI/UX Interface Improvements for Blog Integration)

### Current Interface Architecture Assessment

**✅ Existing Interface Strengths:**
- Clean dashboard with unified business line cards (`index.php`)
- Dynamic single-page approach with slug-based routing (`planner.php`)
- Consistent color theming per business line (Ebone: #23AAC5, Cubofit: #E23633, etc.)
- Responsive design with mobile considerations
- Good separation of concerns with CSS variables and modular styling
- Filter and sort functionality already implemented
- Modal-based interactions for creating new business lines

**❌ Current Interface Limitations for Blog Integration:**

1. **Single Content Type Focus**: Current interface assumes only social media posts
   - Table structure hardcoded for social media fields (redes, imagen_url, etc.)
   - No content type differentiation in UI
   - Missing navigation between content types

2. **Navigation Structure**: Linear navigation doesn't accommodate multiple content types
   - Single table view per business line
   - No tabbed interface or content type selector
   - Filter system only designed for social networks

3. **Content Display**: Table format not optimal for mixed content types
   - Blog posts need different preview format (title, excerpt, categories)
   - Different metadata requirements (tags, SEO, word count vs. social networks)
   - Image handling differs (featured image vs. social media image)

### UI/UX Recommendations for Blog Integration

#### **RECOMMENDATION 1: Tabbed Content Type Interface (PREFERRED)**

**Concept**: Transform `planner.php` into a tabbed interface separating content types

**Benefits**:
- ✅ Clear separation between social posts and blog posts
- ✅ Maintains existing functionality without disruption
- ✅ Scalable for future content types (newsletters, videos, etc.)
- ✅ Familiar UX pattern for users

**Implementation Structure**:
```
[Business Line Header with Logo]
┌─────────────────────────────────────────┐
│ [📱 Posts Sociales] [📝 Blog Posts] [+] │
├─────────────────────────────────────────┤
│ Content-specific filters and actions    │
├─────────────────────────────────────────┤
│ Content-specific table/grid view        │
└─────────────────────────────────────────┘
```

#### **RECOMMENDATION 2: Card-Based Mixed View (ALTERNATIVE)**

**Concept**: Unified timeline with content type indicators

**Benefits**:
- ✅ Holistic content calendar view
- ✅ Better for scheduling coordination
- ✅ Less navigation complexity

**Drawbacks**:
- ❌ Harder to filter by content type
- ❌ Different content types need different layouts
- ❌ More complex responsive behavior

#### **RECOMMENDATION 3: Dashboard Enhancement**

**Current Dashboard Issues**:
- Limited preview of content in business line cards
- No content type breakdown in statistics
- Generic "Nueva Publicación" button

**Suggested Improvements**:
1. **Enhanced Statistics Cards**:
   - Separate counters for social posts vs. blog posts
   - Content type breakdown in hover/expanded view
   - Recent activity timeline per content type

2. **Quick Action Buttons**:
   - Split "Nueva Publicación" into "Nuevo Post Social" + "Nuevo Blog Post"
   - Content type-specific icons and colors

3. **Content Preview Enhancement**:
   - Different preview formats for different content types
   - Better visual hierarchy in cards

### Technical Implementation Considerations

#### **Database Schema Implications**:
- Need `content_type` field in publications table ('social', 'blog')
- Blog-specific fields: `title`, `excerpt`, `categories`, `tags`, `featured_image`
- Social-specific fields remain: `redes_sociales` relationships

#### **URL Structure**:
- Current: `planner.php?slug=business-name`
- Proposed: `planner.php?slug=business-name&type=social|blog`
- Default to social for backward compatibility

#### **CSS/Styling Needs**:
- New tab component styles
- Blog post card/table layouts
- Content type indicators and icons
- Responsive behavior for tabbed interface

## High-level Task Breakdown

### UI/UX INTERFACE ENHANCEMENT FOR BLOG INTEGRATION

#### **PHASE 1: Interface Architecture (2-3 weeks)**

**Task 1.1: Tabbed Interface Implementation (Priority: HIGH)**
- Success Criteria: Functional tab switching between content types
- Deliverables:
  - CSS tab component with business line color theming
  - JavaScript tab switching functionality
  - URL parameter handling for content type
  - Responsive tab behavior for mobile
- Time Estimate: 5-7 days

**Task 1.2: Content Type Detection System (Priority: HIGH)**
- Success Criteria: System can differentiate and route content types
- Deliverables:
  - URL parameter parsing for content type
  - Default content type handling
  - Content type persistence in navigation
  - Breadcrumb/navigation updates
- Time Estimate: 3-4 days

**Task 1.3: Dashboard Statistics Enhancement (Priority: MEDIUM)**
- Success Criteria: Dashboard shows breakdown by content type
- Deliverables:
  - Enhanced statistics cards with content type breakdown
  - Split action buttons for different content types
  - Updated preview formats for mixed content
  - Hover states and expanded views
- Time Estimate: 4-5 days

#### **PHASE 2: Blog-Specific UI Components (2-3 weeks)**

**Task 2.1: Blog Post Table/Card Design (Priority: HIGH)**
- Success Criteria: Blog posts display with appropriate metadata
- Deliverables:
  - Blog post table layout (title, excerpt, categories, status)
  - Featured image handling different from social media images
  - Tag/category display components
  - Blog-specific status indicators
- Time Estimate: 6-8 days

**Task 2.2: Blog Post Form Interface (Priority: HIGH)**
- Success Criteria: User can create/edit blog posts with rich interface
- Deliverables:
  - Blog post creation form with rich text editor
  - Category/tag management interface
  - Featured image upload with preview
  - SEO fields (meta description, slug, etc.)
- Time Estimate: 8-10 days

**Task 2.3: Blog-Specific Filters and Actions (Priority: MEDIUM)**
- Success Criteria: Users can filter and manage blog posts effectively
- Deliverables:
  - Category-based filtering system
  - Tag-based filtering
  - Blog-specific bulk actions
  - Search functionality for blog content
- Time Estimate: 4-6 days

#### **PHASE 3: Integration and Polish (1-2 weeks)**

**Task 3.1: Cross-Content Type Features (Priority: MEDIUM)**
- Success Criteria: Features work consistently across content types
- Deliverables:
  - Unified sharing system for both content types
  - Cross-content type search
  - Mixed content calendar view (optional)
  - Consistent theming across all interfaces
- Time Estimate: 5-7 days

**Task 3.2: Mobile Optimization (Priority: HIGH)**
- Success Criteria: All new interfaces work well on mobile devices
- Deliverables:
  - Mobile-optimized tab interface
  - Responsive blog post cards
  - Touch-friendly interactions
  - Mobile form optimization
- Time Estimate: 3-5 days

### **DESIGN PRINCIPLES TO FOLLOW**:

1. **Consistency**: Maintain existing color schemes and interaction patterns
2. **Scalability**: Design for future content types (newsletters, videos)
3. **Accessibility**: Ensure keyboard navigation and screen reader compatibility
4. **Performance**: Lazy load content and optimize for large datasets
5. **User Mental Model**: Keep familiar patterns, introduce new concepts gradually

### **RISK MITIGATION**:

- **User Confusion**: Implement clear visual cues and onboarding tooltips
- **Mobile Usability**: Prioritize mobile testing throughout development
- **Performance Impact**: Implement pagination and lazy loading from start
- **Data Migration**: Plan for existing data compatibility during transition

## High-level Task Breakdown

### BUSINESS LINE SELECTOR INTERFACE IMPLEMENTATION

#### **PHASE 1: Core Interface Structure (1-2 weeks)**

**Task 1.1: Business Line Dropdown Component (Priority: HIGH)**
- Success Criteria: Functional dropdown with all business lines, proper theming
- Deliverables:
  - CSS dropdown component with business line colors
  - JavaScript dropdown functionality
  - Logo integration in dropdown options
  - URL parameter handling for line switching
  - Responsive dropdown behavior
- Time Estimate: 4-5 days

**Task 1.2: Content Type Tab System (Priority: HIGH)**
- Success Criteria: Working tabs that switch between social/blog with URL persistence
- Deliverables:
  - CSS tab component with business line theming
  - JavaScript tab switching functionality
  - URL parameter management for content type
  - Tab state persistence on page reload
  - Responsive tab behavior for mobile
- Time Estimate: 3-4 days

**Task 1.3: Header Layout Integration (Priority: HIGH)**
- Success Criteria: New header layout replaces current simple header
- Deliverables:
  - Updated header HTML structure
  - Integration with existing navigation
  - Breadcrumb removal (replaced by dropdown)
  - Dashboard back button functionality
  - Mobile header optimization
- Time Estimate: 2-3 days

#### **PHASE 2: Social Network Selector Enhancement (1 week)**

**Task 2.1: Circular Social Network Icons (Priority: HIGH)**
- Success Criteria: Visual circular selector replaces current checkbox filters
- Deliverables:
  - CSS circular icon components
  - Social network icon styling (Instagram, Facebook, Twitter, LinkedIn)
  - Selection state visual feedback
  - Integration with existing filter system
  - Hover and active states
- Time Estimate: 3-4 days

**Task 2.2: Dynamic Network Loading (Priority: MEDIUM)**
- Success Criteria: Network selector updates when business line changes
- Deliverables:
  - JavaScript function to reload networks for selected line
  - AJAX endpoint for getting line-specific networks
  - Smooth transition animations
  - State management for selected networks
- Time Estimate: 2-3 days

#### **PHASE 3: Content Area Adaptation (1 week)**

**Task 3.1: Content Type Routing (Priority: HIGH)**
- Success Criteria: Content area shows appropriate interface based on selected tab
- Deliverables:
  - PHP routing for content type parameter
  - Conditional rendering for social vs blog content
  - Existing social posts table integration
  - Placeholder structure for blog posts
  - Error handling for invalid content types
- Time Estimate: 3-4 days

**Task 3.2: Mobile Optimization (Priority: HIGH)**
- Success Criteria: All new components work well on mobile devices
- Deliverables:
  - Mobile-optimized dropdown (possibly drawer-style)
  - Responsive tab layout
  - Touch-friendly circular selectors
  - Mobile content area optimization
- Time Estimate: 2-3 days

#### **PHASE 4: Integration and Polish (3-5 days)**

**Task 4.1: Business Line Theming Integration (Priority: MEDIUM)**
- Success Criteria: All components respect current business line colors
- Deliverables:
  - Dynamic CSS variable updates based on selected line
  - Consistent theming across all new components
  - Smooth color transitions when switching lines
  - Integration with existing line-specific CSS classes
- Time Estimate: 2-3 days

**Task 4.2: Testing and Bug Fixes (Priority: HIGH)**
- Success Criteria: All functionality works across browsers and devices
- Deliverables:
  - Cross-browser testing
  - Mobile device testing
  - URL parameter edge case handling
  - Performance optimization
  - User acceptance testing
- Time Estimate: 2-3 days

### **DESIGN SPECIFICATIONS**:

#### **Business Line Dropdown**:
- **Style**: Similar to current header but with dropdown functionality
- **Colors**: Each line maintains its brand colors (Ebone: #23AAC5, Cubofit: #E23633, etc.)
- **Logo**: Small logo icon next to line name in dropdown
- **Animation**: Smooth dropdown animation, color transition when switching

#### **Content Type Tabs**:
- **Style**: Clean tab design integrated with header
- **Active State**: Underline or background color matching business line
- **Future-Ready**: "Blog Posts" tab visible but potentially disabled initially
- **Mobile**: Stack vertically or use horizontal scroll on small screens

#### **Social Network Selector**:
- **Style**: Circular icons in a row
- **Selection**: Filled/outlined states for selected/unselected
- **Colors**: Maintain social network brand colors (Instagram gradient, Facebook blue, etc.)
- **Responsive**: Wrap to multiple rows on mobile if needed

### **URL STRUCTURE**:
- **Current**: `planner.php?slug=ebone`
- **New**: `planner.php?slug=ebone&type=social`
- **Blog Ready**: `planner.php?slug=ebone&type=blog`
- **Backward Compatibility**: Default to social if type not specified

### **SUCCESS METRICS**:
1. **Functionality**: All existing features work without regression
2. **Performance**: Page load time not significantly impacted
3. **Usability**: Users can switch between lines and content types intuitively
4. **Responsive**: Works well on desktop, tablet, and mobile
5. **Scalable**: Easy to add new business lines and content types
