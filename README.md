# GatheringStorms
Gathering Storms is a PBEM game in the Forgotten Realms world, converted to Pathfinder ruleset with house rules.

Current tools offered:

/character/ - character sheet based on standard stat block, with addition of location, encounter, and organization sections

/location/ - location sheet based on standard stat block, with addition of inhabitants, encounter, and maps sections.

/encounter/ - encounter (AKA scene) sheet sorted by story and chapter, includes cast and maps sections.

Structure:

/inc
    /controller.inc - BuildOutput object used to pass information from controller to view
    /footer.php
    /gatheringstorms.css
    /gatheringstorms.js - sets loading and saving animations
                        - UniversalHFH object used to give unique ID to new records
                        - AJAX
    /jquery-2.1.1.min.js
    /model.inc - DataCollector object extends Mysqli to handle DB connection and communication
               - setList & getEnum functions to create select lists
               - sanitize function for GET, POST and DB data
               - post_data function to handle POST data passed to the page
    /header.php
    /view.inc - formatting functions (cr_display, dec2hex, ordinal_format, sign_format)
              - build_org function to render an list of organizatiosn graphically
              - buildCharList function to render a list of characters
              - buildLocList function to render a list of locations
              - buildEncList, buildEncGroup & drawEncounter functions to render an ordered list of encounters
              - drawHexMap & drawHexPart functions to render a list of map coordinates graphically
/img - all images
/character
/encounter
/location - all three main-page folders contain index.php to pull together general and custom model, controller and view
          - all three main-page folders contain subfolders, each holding the AJAX-acquired code for a subsection with the same format as the parent


