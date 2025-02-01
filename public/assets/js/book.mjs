"use strict";


/**
 * Prints a Table Of Content based on the available H(n) elements
 *
 * @param start Header Level to start
 * @returns {*}
 */
export function addPageToc(start = 1 ) // : any
{
   const parent = document.querySelector('main');
   if (!parent) {
      console.error('No <main> Element found!');
      return;
   }

   start = 1 ;
   let level = 0;
   let index = 0;
   let tocHtml = '';

   const itemContent = (child) => {
      if (!child.getAttribute('id')) child.setAttribute('id', 'id_' + index);
      index++;
      return `<a href="#${child.id}">${child.innerText.trim()}</a>`;
   }

   for (const child of parent.children) {
      if (child.tagName === `H${level + 1}`) {
         level++;
         if (level > start)  tocHtml += "<ol><li>" + itemContent(child)

      } else if (child.tagName === `H${level}`) {
         if (level > start)  tocHtml += "</li><li>" + itemContent(child)

      } else if (child.tagName === `H${level-1}`) {
         level--;
         if (level > start)  tocHtml += "</li></ol></li><li>" + itemContent(child)
      }
   }
   for(let i = level; i > start; i--) tocHtml += "</li></ol>";

   // insert as next sibling from the h1 element or if not found as the first of the main element
   const pageTocContainer = document.createElement('div');
   pageTocContainer.setAttribute('id', 'pageTocContainer');
   pageTocContainer.innerHTML = tocHtml;

   const H1Element = document.querySelector('H1');
   if(!H1Element) {
      console.error('No <H1> Element found!');
      insertAfter(pageTocContainer, parent);
   } else {
      insertAfter(pageTocContainer, H1Element);
   }


}

export function insertAfter(newNode, existingNode) {
   existingNode.parentNode.insertBefore(newNode, existingNode.nextSibling);
}






