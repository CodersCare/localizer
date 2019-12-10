.. include:: ../Includes.txt


.. _configuration:

=============
Configuration
=============

The Localizer will be configured using Localizer Settings Records only, so there is no TSconfig or other TypoScript you need to take care of and no plugin or static template to be included.

.. important::
    Since Localizer settings are tied to particular pages and their branches you need to create those records within a real page to get a ``pid`` value. The root page (0) wil not work.

To create one or more Localizers for your editors, just

#. Go to any real page and click on "Create New Record"
#. Within the group "Localizer for TYPO3" click on "Localizer Settings"
#. Fill in the necessary fields according to the instructions below
#. Save the records to make the new Localizer available for your editors

..  tip::
    If something went wrong while creating the record, you will find an error message in the "Last Communication Error" box and the record will be disabled.

After creating the records, make sure to add the necessary tasks to the Scheduler, so that the automatic workflow can be triggered via a cron job. Each of these tasks has to be recurring and should be set to a time that matches your usual workflow timing best.

.. _figure2:
.. figure:: ../Images/Screenshots/Scheduler.png
   :class: with-shadow
   :alt: Localizer Scheduler Tasks
   :width: 300px

   Scheduler Tasks

.. tip::
    To avoid performance problems during the import, only a single file will be imported during each scheduler run, so you should configure the time for the importer task accordingly to reduce the time your editors have to wait until each of their translation tasks has been imported..

Settings
========

The basic version of the Localizer provides you with the type "Universal FTP hot folders" only. To configure this type, you have to fill in the following fields.

.. _ServerType:

Server Type
"""""""""""
.. container:: table-row

   Property
         Server Type
   Data type
         selector
   Description
         If there are Localizer API extensions installed, you can select one of the available server types here. This will change the available configuration fields accordingly.

.. _Title:

Title
"""""
.. container:: table-row

   Property
         Title
   Data type
         string (mandatory)
   Description
         This is the title of the Localizer, which will be used in selection drop downs for your editors in the Localizer Selector and the Localizer Cart

.. _Description:

Description
"""""""""""
.. container:: table-row

   Property
         Description
   Data type
         string (optional)
   Description
         This is the description of the Localizer to add some information for project management purposes

.. _PathToOutgoingHotFolder:

Path to outgoing hot folder
"""""""""""""""""""""""""""
.. container:: table-row

   Property
         Path to outgoing hot folder
   Data type
         string (mandatory)
   Description
         This is the path to the outgoing hot folder relative to your web root. If it is not set, the record will be disabled automatically. If it does not exist yet, it will be created during on save.

.. important::
    Make sure you have proper read and write access to that path, otherwise the creation of the folder or the export files might fail.

.. _PathToIncomingHotFolder:

Path to incoming hot folder
"""""""""""""""""""""""""""
.. container:: table-row

   Property
         Path to incoming hot folder
   Data type
         string (mandatory)
   Description
         This is the path to the incoming hot folder relative to your web root. If it is not set, the record will be disabled automatically. If it does not exist yet, it will be created during on save.

.. important::
    Make sure you have proper read and write access to that path, otherwise the creation of the folder or the import files might fail.

.. _Workflow:

Workflow
""""""""
.. container:: table-row

   Property
         Workflow
   Data type
         string (optional)
   Description
         Additional information for the translation service provider, which of the predefined workflows within the translation process should be used. This will be written into an additional instruction.xml file sent together with the L10nmgr export.

.. _ProjectKey:

Project Key
"""""""""""
.. container:: table-row

   Property
         Project Key
   Data type
         string (optional)
   Description
         Additional information for the translation service provider, to assign the incoming job to a specific project. This will be written into an additional instruction.xml file sent together with the L10nmgr export.

.. _AllowEditorsToAddPagesForAutomaticExport:

Allow editors to add pages for automatic export
"""""""""""""""""""""""""""""""""""""""""""""""
.. container:: table-row

   Property
         Allow editors to add pages for automatic export
   Data type
         checkbox (optional)
   Description
         This switch enables editors to select a particular localizer configuration form within the language tab of a page record. If selected the page will be recognized during the automatic export scheduler task and automatically create a translation cart. Use this for a specific combination of page and localizer configuration.

.. _PagesAddedByEditorsForAutomaticExport:

Pages added by editors for automatic export
"""""""""""""""""""""""""""""""""""""""""""
.. container:: table-row

   Property
         Pages added by editors for automatic export
   Data type
         multiple select (read only)
   Description
         Show a list of pages that will be recognized during the automatic export scheduler task and automatically create a translation cart for this localizer configuration.

.. _MinimumAge:

Minimum age
"""""""""""
.. container:: table-row

   Property
         Minimum age of the latest change (min) after that a record will be scheduled for automatic export.
   Data type
         integer (optional)
   Description
         Minimum age of the latest change (min) after that a record will be scheduled for automatic export.

.. _CollectPagesMarkedByEditorsForAutomaticExport:

Collect pages marked by editors for automatic export
""""""""""""""""""""""""""""""""""""""""""""""""""""
.. container:: table-row

   Property
         Collect pages marked by editors for automatic export
   Data type
         checkbox (optional)
   Description
         This switch enables editors to activate a page record for automatic exports. If selected the page will be recognized during the automatic export scheduler task and automatically create a translation cart for each of the active localizer configuration. Use this for common pages that should always be exported.

.. _AllowedL10nmgrConfigurations:

Allowed L10nmgr Configurations
""""""""""""""""""""""""""""""
.. container:: table-row

   Property
         Allowed L10nmgr Configurations
   Data type
         multiple select (leave empty)
   Description
         This field will be automatically filled during the translation process. It will always contain the latest configuration generated by the Localizer, so that you can try to debug the situation in case of an error.

.. _SourceLanguage:

Source Language
"""""""""""""""
.. container:: table-row

   Property
         Source Language
   Data type
         multiple select (mandatory)
   Description
         Select the official source language for this Localizer. Source languages can be different for different Localizers i.e. for scenarios with multiple source languages for multiple sites within a single TYPO3 instance.

.. important::
    Make sure each of the language records has been configured with the necessary locales to distinguish between languages during the translations process.

.. _TargetLanguage:

Target Language
"""""""""""""""
.. container:: table-row

   Property
         Target Language
   Data type
         multiple select (mandatory)
   Description
         Select the official target languages available for this Localizer. Target languages can be different for different Localizers i.e. for scenarios with different translation service providers for different target languages within a single TYPO3 instance.

.. important::
    Make sure each of the language records has been configured with the necessary locales to distinguish between languages during the translations process.
