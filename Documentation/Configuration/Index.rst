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
    If something went wrong while creating the record, you will find an error message in the "Last Communication Error" box and the recrod will be disabled.

Settings
========

The basic version of the Localizer provides you with the type "Universal FTP hot folders" only. TRo configure this type, you have to fill in the following fields.

.. _ServerType:

Server Type
"""""""""""
.. container:: table-row

   Property
         Server Type
   Data type
         selector
   Description
         If there are Localizer API extensions installed, you can select on of the available server types here. This will change the available configuration fields accordingly.

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
    Make sure you have proper read and write access to that path, otherwise the creation of the folders or the export files might fail.

