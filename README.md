# Volta\Component\Books
 
## Features
- A portable Book Format
- Customizable content parsers for different content types
- Web and EPUB publisher

## Intro 
In the realm of Volta Books, we encounter a distinctive form of book that operates on files. These books are comprised of two fundamental elements: **DocumentNodes** and **ResourceNodes**. Within this structure, each **DocumentNode** has the potential to be linked to zero or more other **DocumentNodes**. When a **DocumentNode** exists independently, without a parent, it assumes the role of either a **RootNode** or **BookNode**.

To earn the classification of a **DocumentNode**, specific criteria must be met. A directory must contain two obligatory files within it. The first file is named "`content.*`" and serves as the repository for the document's content. The second file, "`meta.json`," is essential, and it must adhere to the standards of a valid JSON file, serving as the storage space for crucial metadata. Volta Books are versatile in the types of content they accept, including plain text files, HTML, XHTML files, and even PHP files.

## usage

 

To get a **`Node`**, any **`Node`**, we pass the (absolute) path to the `Node::factory()` or the relative path to the `Node::getChild()` method of a **`Node`** instance. These methods will return a valid **`Node`** or will raise an Exception if the path does not contain a **`Node`**.
 