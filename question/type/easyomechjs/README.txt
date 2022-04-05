Moodle 2.3+ plugin: EasyOChem MarvinJS Mechanism (EasyOMechJS) question type
Visit www.easyochem.com for demos and further documentation.

Carl LeBlond


INSTALLATION:

This will NOT work with Moodle 2.0 or older, since it uses the new
question API implemented in Moodle 2.1.

This is a Moodle question type. It should come as a self-contained 
"easyomechjs" folder which should be placed inside the "question/type" folder
which already exists on your Moodle web server.

Once you have done that, visit your Moodle admin page - the database 
tables should automatically be upgraded to include an extra table for
the EasyOChem Mechanism question type.

You must download a recent copy of MarvinJS from www.chemaxon.com (free for academic use)
and install it in folder named "marvin4js" at your web root.  There is a admin setting in
Moodle so you can change this setting.


USAGE:

The EasyoChem Curved Arrow / Electron Pushing question can be used to test and strengthen 
students knowledge of reaction mechanism, resonance and curved arrow notation.

You can ask questions such as;

    Please add curved arrows showing the flow of electrons for the following reaction?
    Please add curved arrows showing how the following resonance structure could be obtained?

##### Moodle Team UofL ######
We have downloaded MarvinJs from https://chemaxon.com/products/marvin-js/download
Version: marvinjs-18.5.0-core(without example)  marvinjs-18.5.0-all(with example)
Rename folder with marvinjs and keep in root level. i.e parallel to question, local, course folder.
Set marvinjs path while installing EasyOChem plugin : /201801/marvinjs
Check marvinjs(folder) has full permission otherwise give full permission. Without permission it will not load the frame.
