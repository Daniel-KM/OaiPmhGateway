OAI-PMH Gateway (plugin for Omeka)
==================================

[OAI-PMH Gateway] is a plugin for [Omeka] that exposes files and metadata of a
standard [OAI-PMH static repository] through Omeka used as a gateway.
[OAI-PMH static repository] is an implementation of the well-known standard
[OAI-PMH] of the [Open Archives] organization. So with this plugin, any OAI-PMH
harvester can harvest a static repository exposed by Omeka.

Such a static repository is a simple xml file that can be built with any editor.
The build process can be automated with the plugin [OAI-PMH Static Repository], that
transforms any local or remote directory of files and/or metadata files into a
standard static repository.

So, to try it quickly, install [OAI-PMH Repository], [OAI-PMH Harvester], [OAI-PMH Gateway],
and [OAI-PMH Static Repository] and follow the instructions in the [readme of OAI-PMH Static Repository].
You can use the included official example too.


Included official example
-------------------------

The example of static repository provided by [Open Archives] is included at
"tests/suite/_files/www.openarchives.org_StaticRepositoryExample.xml".

To try it, you need to:

- Install [OAI-PMH Repository] or the [improved fork] and this plugin.
- Copy and rename this file in a location that the server can access.
- Change with a simple text editor the base url inside the xml to indicate that
this static repository is exposed via the gateway provided by the plugin:
```xml
<oai:baseURL>http://gateway.institution.org/oai/an.oai.org/ma/mini.xml</oai:baseURL>
```
by something like this, using localhost or the domain, and the path to the file:
```xml
<oai:baseURL>http://localhost/MyOmeka/gateway/localhost/path/to/StaticRepositoryExample.xml</oai:baseURL>
```
- Click on "Check". If there is an error, check the base url and the access
rights of the server to the xml file, then reinitiate the gateway on this url by
resetting the status.
- Click on the provided url and the content of the static repository appears.
- If [OAI-PMH Harvester] is installed, click on "Harvest", and accept default
values. Two items will be created with metadata, and a collection for them.

The content of the static repository can be updated and the harvester will
update imported items automatically.

The "Public" switch allows to spread the static repository to the world, or to
keep it internally, for testing purpose of for local ingest.

The "Friend" switch allows to add it to the list of static repositories that
this gateway manages, even if records are not ingested by Omeka. The list is
viewable with the OAI-PMH verb "Identify". See [OAI-PMH Confederated Repositories] for more
details.


Installation
------------

Install first the plugin [OAI-PMH Repository].

Note: the official [OAI-PMH Repository] has been completed in an [improved fork],
in particular with a human interface, and until merge of the commits, the latter
is recommended.

You can install [OAI-PMH Harvester] too, specially if you use [OAI-PMH Static Repository]
to ingest files and/or metadata.

Note: the official [OAI-PMH Harvester] can only ingest standard metadata
(elements). If you want to ingest other standards, files and metadata of files,
extra data, and to manage records, you should use the fixed and improved [fork]
of it.

Then uncompress files and rename plugin folder `OaiPmhGateway`.

Then install it like any other Omeka plugin and follow the config instructions.

Two parameters can be set in the file `config.ini` to manage harvestings.

To expose remote repositories and files via http/https, the server should have
access to them.


TODO
----

Some specifications related to the cache, the timeout, and the systematic check
of the original url are not fully implemented.


Warning
-------

Use it at your own risk.

It’s always recommended to backup your files and your databases and to check
your archives regularly so you can roll back if needed.


Troubleshooting
---------------

See online issues on the [plugin issues] page on GitHub.


License
-------

This plugin is published under the [CeCILL v2.1] licence, compatible with
[GNU/GPL] and approved by [FSF] and [OSI].

In consideration of access to the source code and the rights to copy, modify and
redistribute granted by the license, users are provided only with a limited
warranty and the software's author, the holder of the economic rights, and the
successive licensors only have limited liability.

In this respect, the risks associated with loading, using, modifying and/or
developing or reproducing the software by the user are brought to the user's
attention, given its Free Software status, which may make it complicated to use,
with the result that its use is reserved for developers and experienced
professionals having in-depth computer knowledge. Users are therefore encouraged
to load and test the suitability of the software as regards their requirements
in conditions enabling the security of their systems and/or data to be ensured
and, more generally, to use and operate it in the same conditions of security.
This Agreement may be freely reproduced and published, provided it is not
altered, and that no provisions are either added or removed herefrom.


Contact
-------

Current maintainers:

* Daniel Berthereau (see [Daniel-KM] on GitHub)


Copyright
---------

* Copyright Daniel Berthereau, 2015


[OAI-PMH Gateway]: https://github.com/Daniel-KM/Omeka-plugin-OaiPmhGateway
[Omeka]: https://omeka.org
[OAI-PMH static repository]: http://www.openarchives.org/OAI/2.0/guidelines-static-repository.htm
[OAI-PMH]: https://www.openarchives.org/OAI/2.0/openarchivesprotocol.htm
[Open Archives]: https://www.openarchives.org
[OAI-PMH Static Repository]: https://github.com/Daniel-KM/Omeka-plugin-OaiPmhStaticRepository
[OAI-PMH Repository]: https://omeka.org/add-ons/plugins/oai-pmh-repository
[OAI-PMH Harvester]: https://omeka.org/add-ons/plugins/oai-pmh-harvester
[readme of OAI-PMH Static Repository]: https://github.com/Daniel-KM/Omeka-plugin-OaiPmhStaticRepository
[OAI-PMH Confederated Repositories]: https://www.openarchives.org/OAI/2.0/guidelines-friends.htm
[improved fork]: https://github.com/Daniel-KM/Omeka-plugin-OaiPmhRepository
[fork]: https://github.com/Daniel-KM/Omeka-plugin-OaipmhHarvester
[plugin issues]: https://github.com/Daniel-KM/Omeka-plugin-OaiPmhGateway/issues
[CeCILL v2.1]: https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html
[GNU/GPL]: https://www.gnu.org/licenses/gpl-3.0.html
[FSF]: https://www.fsf.org
[OSI]: http://opensource.org
[Daniel-KM]: https://github.com/Daniel-KM "Daniel Berthereau"
