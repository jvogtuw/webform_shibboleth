Webform Shibboleth
===
![Webform Shibboleth version 1.0.0](https://img.shields.io/static/v1?label=version&message=v1.0.0&color=green)

This module provides the option to put a Webform behind Shibboleth protection
without requiring the Shibboleth path module.

Use
---

To enable Shibboleth protection for a Webform, go to the form's Access settings
(/admin/structure/webform/manage/{webform}/access). Under Create submissions,
check the box for **Require Shibboleth authentication**.

NOTE: This module only impacts the Webform accessed at its own page. It doesn't
have an effect on Webforms placed in blocks, modals, etc.

Permissions
---

Users with permission to create or edit Webforms can manage this setting.

Advanced access control
---

This module can only enable the Shibboleth login requirement. For more granular
control based on UW Groups or affiliation, use the Shibboleth path module.
