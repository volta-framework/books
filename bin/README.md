# Volta\Component\Books - Bin

This element comprises a solitary command-line script named `VBtoEPub.php` (signifying **Volta Book** to **EPUB**). The script accepts a pair of arguments. The initial argument represents the complete pathway to a legitimate **Volta Book**, while the second designates the directory path for storing the resulting **EPUB** files

```shell
~$ php VBtoEpub.php <FROM> <TO>
```

Response will be provided in the event that either of the two arguments is deemed invalid. Should the target directory encompass files, the script will request permission prior to purging all files within.

## Creation of the *.epub file on Linux
At present, the generation of the `*.epub` file occurs automatically on Linux systems. However, Windows and iOS platforms are not yet supported. This implies that on these platforms, the creation of the file needs to be performed manually after script execution.

Once executed, you will locate the file within the destination folder, adjacent to the `src` subfolder where all the EPUB source content is stored. It's also possible to recreate the EPUB manually whenever needed. Refer to the subsequent section for instructions on how to accomplish this.


## Manually Create the *.epub file (Windows or IOS)

Within the target directory, you will find a single subfolder named `src`, housing all the source files required for the creation of the *.epub file. An `*.epub` file is essentially a compressed archive. I suggest utilizing 7-Zip for crafting the archive. Assign a suitable filename to the archive and then modify its extension to .epub – and just like that, your EPUB will be ready for use!