"use strict";


/**
 * Prints a Table Of Content based on the available H(n) elements
 *
 * @param start Header Level to start
 * @returns {*}
 */
export function addPageToc(start = 1 ) // : any
{
   let parent = document.querySelector('main');
   if(!parent) {
      console.error('No <main> Element found!');
      return;
   }

   let H1Element = document.querySelector('H1');
   if(!H1Element) {
      console.error('No <H1> Element found!');
      return;
   }

   let level = 0;
   let index = 0;
   let tocHtml = '';

   for (const child of parent.children) {

      if (child.tagName === 'H' + (level+1)) {
          level++;
          tocHtml += "<ul>";
      }
      if (start === level) {
         level++;
         continue;
      }
      if (child.tagName === 'H' + level) {
         child.setAttribute('id', 'id_' + index);
         tocHtml += `<li>${'  '.repeat((level+2))}<a href="#${child.id}">${child.innerText.trim()}</a>`;
      }
      if (child.tagName === 'H' + (level-1)) {
         level--;
         tocHtml += "</li></ul>";
      }
      index++;
   }

   // insert as next sibling from the h1 element
   let pageTocContainer = document.createElement('div');
   pageTocContainer.setAttribute('id', 'pageTocContainer');
   pageTocContainer.innerHTML = tocHtml;

   insertAfter(pageTocContainer, H1Element);

}
export function insertAfter(newNode, existingNode) {
   existingNode.parentNode.insertBefore(newNode, existingNode.nextSibling);
}






