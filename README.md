# Datasource Select Box Field

- Version: 0.1
- Author: Jonathan Mifsud (info@jonmifsud.com)
- Build Date: 3nd March 2012
- Requirements: Symphony 2.2+
- Dependency: Pages Field


## Installation

1. Upload the `datasourcefield` folder in this archive to your Symphony `/extensions` folder.

2. Enable it by selecting the "Field: Datasource Select Box", choose Enable from the with-selected menu, then click Apply.

3. You can now add the "Datasource Select Box" field to your sections.

## Use

In order for this field to do it's magic it requires to be linked to from a section that contains a `Pages Field`. 

For example your `Datasource Field` is contained within a `Widgets` seciton to which some custom template can be applied. And thus you want to be able
to link your datasource to this `widget` so as the user can select the widgets to display on the page without having to add the datasources manually.

Then you have a `Page Widgets` section which is specifically for the user to link what widgets should appear on what pages. This Section must contain at least
a `Pages Field` and a link to your widgets section. The link can be either a symphony `Select Box Link` or else `Subsection Manager` (1.x tested but 2 should be supported).

Whenever you save an entry in `Page Widgets` the extension will cross-check data from these related fields; find any datasources that should be attached and makes sure that
they are at least part of the list on the Symphony Page by adding the datasources and making a unique filter to remove duplicates.

At this point in time this DOES NOT remove any datasources as if you cross-use datasources in between widgets and independently of the pages you can risk the user removing
valuable datasources and breaking the expected design / functionability.

### Note - Renaming Datasources

Please pay attention when renaming your datasources to go back in the section and update your data to match the new datasource names. This has not been throughly 
tested so use with caution.

## Future Enhancments

1. The option to have your datasource field remove datasources from pages, through a checkbox.

2. Handling of datasource renaming to update field data.