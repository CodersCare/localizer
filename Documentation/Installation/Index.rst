.. include:: ../Includes.txt



.. _installation:

============
Installation
============

.. tip::

   Localizer versions are always matching the TYPO3 version you are running. So version 8.x should be installed with CMS 8.7 only, 9.x with CMS 9.5 and so on.

The extension needs to be installed as any other extension of TYPO3 CMS:

#. Switch to the module "Extension Manager".

#. Get the extension

   #. **Get it from the Extension Manager:** Press the "Retrieve/Update"
      button, search for the extension key *localizer* and import the
      extension from the TYPO3 extension repository.

   #. **Use composer**: Use `composer require localizationteam/localizer`.

Latest version from git
-----------------------
You can get the latest version from git by using the git command:

.. code-block:: bash

   git clone git@gitlab.com:Coders.Care/localizer.git

.. important::

   The master branch supports the latest TYPO3 version only. Other branches are numbered accordingly. Use i.e. the branch ``8-0`` if you are using TYPO3 CMS 8!