.. include:: ../Includes.txt

.. _installation:

============
Installation
============

Just install this extension the usual way like any other TYPO3 extension.

.. rst-class:: bignums-xxl

1. Get the extension
====================

   .. rst-class:: bignums

   #. Switch to the module “Extension Manager”.

   #. Get the extension

   #. **Get it from the Extension Manager:**
      Press the “Retrieve/Update” button and search for the extension key
      *ns_ext_compatibility* and import the extension from the repository.

   #. **Get it from typo3.org:** You can always get the current version from
      https://extensions.typo3.org/extension/ns_ext_compatibility/ by downloading either
      the t3x or zip version. Upload the file afterwards in the Extension Manager.

   .. figure:: Images/TYPO3_ns_ext_compatibility_Extension_NITSAN_Backend_Install_Extensions.png
       :alt: installation with the extension manager in the backend
       :width: 1300px


2. Activate the TypoScript
==========================

   The extension ships some static TypoScript code which needs to be included.

   .. rst-class:: bignums

   #. Switch to the root page of your site.

   #. Switch to the **Template module** and select *Info/Modify*.

   #. Click the link **Edit the whole template record** and switch to the tab
      *Includes*.

   #. Select **[NITSAN] ns_ext_compatibility** at the field *Include static
      (from extensions):*

   #. Include **[NITSAN] ns_ext_compatibility** at the last place.

   .. figure:: Images/TYPO3_ns_ext_compatibility_Extension_NITSAN_Backend_Install2_Extensions.png
       :alt: installation 2
       :width: 1300px