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


