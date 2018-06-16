.. include:: ../Includes.txt

.. _configuration:

=============
Configuration
=============

1. Compulsory: Update Extension Repository
==========================================

   To make works perfect this extension, It's very important to-do perform this task. By d way, you can also setup schedular to make it automatically update ;) You can easily update extension repository with following simple steps:

   .. rst-class:: bignums

   #. Go to “Extension Manager”.

   #. Select "Get Extensions" from Dropdown

   #. Click on 'Update Now' button

   .. figure:: Images/1-ns_ext_compatibility_config-3.png
        :alt: Compatibility Check Configuration 3
        :width: 1300px


2. Optional: Set default target TYPO3 version
=============================================

 	.. figure:: Images/1-ns_ext_compatibility_config-1.png
            :alt: Compatibility Check Configuration 1
            :width: 1300px

 	.. figure:: Images/1-ns_ext_compatibility_config-2.png
            :alt: Compatibility Check Configuration 2
            :width: 1300px

3. Optional: Configure Scheduler
=================================

  .. rst-class:: bignums

  #. Go to SYSTEM > **Scheduler** > Create new scheduler with **Update extension list (extensionmanager)**.

  .. figure:: Images/1-ns_ext_compatibility_scheduler_1.png
          :alt: Compatibility Check Scheduler 1
          :width: 1300px

  .. rst-class:: bignums

  #. Go to SYSTEM > **Scheduler** > Create new scheduler with **TYPO3 Extensions Compatibility Report via Email Notification**, It will automatically send you an email whenever new TYPO3 version or extensions will available, Please configure all the fields.

  .. figure:: Images/1-ns_ext_compatibility_scheduler_2.png
          :alt: Compatibility Check Scheduler 1
          :width: 1300px
  .. figure:: Images/1-ns_ext_compatibility_scheduler_3.png
          :alt: Compatibility Check Scheduler 1
          :width: 1300px


That's it, Now you can enjoy all the benifits of this extension :)