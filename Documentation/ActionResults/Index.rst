.. include:: ../Includes.txt

.. |true-icon| image:: Images/righ-icon.png
.. |false-icon| image:: Images/crose-icon.png
.. |view-icon| image:: Images/view.png
.. |version-icon| image:: Images/version.png
.. |documentation-icon| image:: Images/documentation.png
.. |typo-icon| image:: Images/typo.png


.. _Action_And_Results:

===========
User Manual
===========

1. Select TYPO3 Target version and Export Feature
================================================

   Now you may able to access backend module at **ADMIN TOOLS > Extensions Report**

   .. rst-class:: bignums

   #. You can select your target TYPO3 version to generate report.

   #. By clicking on *"Export Report"* button, Extension will export whole report in Excel sheet format

    .. figure:: Images/1-ns_ext_compatibility_action.png
            :alt: ns ext compatibility action 1
            :width: 1300px


2. System Information, Extensions Statistics, Server compatibility report
=========================================================================

    .. rst-class:: bignums

    #. The **System Information** section shows general overview report.

    #. **Extensions Statistics** section shows statistics of extensions eg., How many extensions are installed?

    #. **Server compatibility report** section shows the comparison of "server compatibility" between installed and target TYPO3 version.

    .. figure:: Images/2-ns_ext_compatibility_action.png
            :alt: ns ext compatibility action 2
            :width: 1300px


3. TYPO3 Extensions Report
==========================

     Here, you can see list of all the TYPO3 extensions with checking compatibility, available new version, variance actions eg., history of extension, versions etc.,

     .. figure:: Images/4-ns_ext_compatibility_action.png
            :alt: ns ext compatibility action 3
            :width: 1300px


4. Actions And Results
======================

    This part shows which icon contains what kind of effect in it.

    ==================== ============================== =======================================================================================
    Icon                 Action                         Description
    ==================== ============================== =======================================================================================
    |true-icon|          **Compatible OR Installed**    The currently installed extension version is already compatible with LTS version of

                                                        TYPO3 CMS or Installed in to system.
    |false-icon|         **Non-Compatible OR**          The currently installed extension version is not compatible with LTS version of
                         **Not-Installed**              or not installed in to system.

    |view-icon|          **Extension Details**          It will show all the details of the extension which you have clicked like Extension Key,
                                                        Description, Last Updated Comment, Last Updated Date, etc.

    |version-icon|       **Extension Version Details**  It will show all the details of the extension as well as all extension's versions which
                                                        uploaded at TER.

    |documentation-icon| **Documentation**              It will redirect you to the TER Doccumentation page which you have clicked.

    |typo-icon|          **TER Extension**              It will redirect you to the **https://extensions.typo3.org** of respective extension.
    ==================== ============================== =======================================================================================