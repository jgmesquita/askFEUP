// ----------- Global Variables -----------
let tags;
let csrfToken;
// ----------------------------------------

const dropdownBtns = document.querySelectorAll('.dropdown');

// Clicks in the document
document.addEventListener('click', function(event) {
  // Hide dropdowns 
  const dropdown = document.getElementById('customDropdown');
  const actionDropdowns = document.querySelectorAll('.dropdown-content');

  actionDropdowns.forEach(actionDropdown => {
    actionDropdown.classList.add('hidden');
  });

  if (dropdown && !dropdown.contains(event.target)) {
      dropdown.classList.remove('open');
  }

  // Hide search-helper 
  const searchInput = document.querySelector('.search input');
  const searchHelper = document.querySelector('.search-helper');
  
  if (searchInput && searchHelper && !searchInput.contains(event.target) && !searchHelper.contains(event.target)) {
    searchHelper.classList.add('hidden'); 
  }
});

// Opens a dropdown if button is clicked OR closes all opened ones
function toggleDropdown(e) {
  const dropdownBtn = e.target.closest('.dropdown'); 
  if (dropdownBtn) {
    e.stopPropagation();

    const dropdownContent = dropdownBtn.querySelector('.dropdown-content');
    
    // Hide all dropdowns
    document.querySelectorAll('.dropdown-content').forEach(content => {
      if (content !== dropdownContent) {
        content.classList.add('hidden');
      }
    });

    dropdownContent.classList.toggle('hidden');
  } else {
    document.querySelectorAll('.dropdown-content').forEach(content => {
      content.classList.add('hidden');
    });
  }
};

// Get all tags from the database
async function getAllTags() {
  try {
    const response = await fetch('/api/tags', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        }
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    return await response.json();
  } catch (error) {
      renderMessage('error', `Error fetching tags: ${error}`);
      return null;
  }
}

// Navigate to a question from home and profile
function navigateToQuestion(currentPage, type, url) {
  if (currentPage !== "home" 
      && currentPage !== "profile" 
      && currentPage !== "profile.section" 
      && currentPage !== 'render.section'
      && currentPage !== 'questions.followed'
      && currentPage !== 'manager.section') {
    return;
  }
  window.location.href = url; 
}

// Update selected tab
function updateSelectedTab(tab) {
  const tabs = document.querySelectorAll('.content-nav .tab');
  tabs.forEach(tab => {
      tab.classList.remove('selected');
  })
  tab.classList.add('selected');
}

// Edit the tag of a question
async function editTag(postId, postTag) {
  const contentPost = document.querySelector(`.question .content-post[data-item-id="${postId}"]`);
  if (!csrfToken) csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const editForm = document.querySelector('.edit-post');
  if (editForm) {
    const editPost = editForm.closest('.content-post');
    const editPostId = editPost?.getAttribute('data-item-id');
    cancelEdit(editPostId);
  }
 
  const originalTag = contentPost.querySelector('.tag');
  const originalActions = contentPost.querySelector('.action-items');
  const dropdown = contentPost.previousElementSibling;
  
  if (!originalActions || !dropdown) return;

  originalTag?.classList.add('hidden');
  originalActions.classList.add('hidden');
  dropdown.classList.add('hidden');

  // Get current tag
  if (postTag && typeof postTag === 'string') {
    postTag = JSON.parse(postTag);
  }

  // Get all tags
  if (!tags) tags = await getAllTags();

  // Build tags dropdown options
  let tagsHTML = '';
  tags.forEach(tag => {
    const isSelected = tag.id === postTag.id ? ' class="selected"' : ''; 
    tagsHTML += `<li${isSelected} onclick="selectOption(${tag.id}, '${tag.name}', 'tag')">${tag.name}</li>`;
  });

  const html = `
  <form class="edit-post .question" method="POST" action="/api/questions/${postId}/editTag" onclick="event.stopPropagation()">
    <input type="hidden" name="_token" value="${csrfToken}">
    <div class="button-group">
      <div class="custom-dropdown" id="customDropdown">
        <button type="button" class="dropdown-button" onclick="toggleTagDropdown()">
          ${postTag.name || 'Select a tag'}
        </button>
        <ul class="dropdown-options" id="dropdownOptions">
          ${tagsHTML}
        </ul>
        <input type="hidden" id="tag" name="tag" value="${postTag.id || ''}" required>
      </div>
      <button type="button" onclick="cancelEdit(${postId})">Cancel</button>
      <button type="submit">Save Changes</button>
    </div>
  </form>`;
  
  const tempContainer = document.createElement('div');
  tempContainer.innerHTML = html;
  contentPost.append(tempContainer.firstElementChild);
}

// Edit the content of a question
async function editFunction(type, postId, postTitle, postText, postTag) {
  const contentPost = document.querySelector(`.${type} .content-post[data-item-id="${postId}"]`);
  if (!contentPost) return;

  if (!csrfToken) csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const editForm = document.querySelector('.edit-post');
  if (editForm) {
    const editPost = editForm.closest('.content-post');
    const editPostId = editPost?.getAttribute('data-item-id');
    cancelEdit(editPostId);
  }
 
  const originalContent = contentPost.querySelector('.text-post');
  const originalTag = contentPost.querySelector('.tag');
  const originalActions = contentPost.querySelector('.action-items');
  const dropdown = contentPost.previousElementSibling;
  
  if (!originalContent || !originalActions || !dropdown) return;

  originalTag?.classList.add('hidden');
  originalContent.classList.add('hidden');
  originalActions.classList.add('hidden');
  dropdown.classList.add('hidden');

  // Get current tag
  if (postTag && typeof postTag === 'string') {
    postTag = JSON.parse(postTag);
  }

  // Get all tags
  if (!tags) tags = await getAllTags();

  // Build tags dropdown options
  let tagsHTML = '';
  if (type === "question") {
    tags.forEach(tag => {
      const isSelected = tag.id === postTag.id ? ' class="selected"' : ''; 
      tagsHTML += `<li${isSelected} onclick="selectOption(${tag.id}, '${tag.name}', 'tag')">${tag.name}</li>`;
    });
  }

  const html = `
  <form class="edit-post ${type}" method="POST" action="/api/${type}s/${postId}/edit" onclick="event.stopPropagation()">
    <input type="hidden" name="_token" value="${csrfToken}">
    ${type === 'question' ? `<input type="text" name="title" value="${postTitle}" placeholder="Edit Title">` : ''}
    <textarea name="text" placeholder="Edit Text">${postText}</textarea>
    <div class="button-group">
    ${type === 'question' ? 
      `<div class="custom-dropdown" id="customDropdown">
        <button type="button" class="dropdown-button" onclick="toggleTagDropdown()">
          ${postTag.name || 'Select a tag'}
        </button>
        <ul class="dropdown-options" id="dropdownOptions">
          ${tagsHTML}
        </ul>
        <input type="hidden" id="tag" name="tag" value="${postTag.id || ''}" required>
      </div>` : ''}   
      <button type="button" onclick="cancelEdit(${postId})">Cancel</button>
      <button type="submit">Save Changes</button>
    </div>
  </form>`;
  
  const tempContainer = document.createElement('div');
  tempContainer.innerHTML = html;
  contentPost.prepend(tempContainer.firstElementChild);
}

// Cancel editing of a question
function cancelEdit(postId) {
  const contentPost = document.querySelector(`.content-post[data-item-id="${postId}"]`);
  const form = contentPost.querySelector('form');
  const originalContent = contentPost.querySelector('.text-post');
  const originalTag = contentPost.querySelector('.tag');
  const originalActions = contentPost.querySelector('.action-items');
  const dropdown = contentPost.previousElementSibling;

  if (form && originalContent && originalActions && dropdown) {
    form.remove(); 
    originalTag?.classList.remove('hidden');
    originalContent.classList.remove('hidden'); 
    originalActions.classList.remove('hidden');
    dropdown.classList.remove('hidden');
  }
}

function toggleCreateComment(type, postId) {
  const answer = document.querySelector(`.${type} .content-post[data-item-id="${postId}"]`)
  const newComment = answer.querySelector('.new-comment');

  if (newComment) {
    newComment.remove();
    return;
  }

  if (!csrfToken) csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const commentFormContainer = document.createElement('div');
  commentFormContainer.innerHTML = `
    <div class="comment" onclick="event.stopPropagation()">
      <form class="new-comment" method="POST" action="/questions/answers/${postId}/comments">
        <input type="hidden" name="_token" value="${csrfToken}">
        <textarea id="newComment" name="newComment" placeholder="Add a comment" required></textarea>
        <div class="button-group">
            <button type="submit">Comment</button>
        </div>
      </form>
    </div>
  `
  answer.appendChild(commentFormContainer);
}

// Create report content popup
async function openReportPopup(type, postId) {
  try {
    if (!csrfToken) csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    const response = await fetch(`/api/report-reasons`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const reportReasons = await response.json();

    const optionsHtml = reportReasons
      .map(reason => `
        <li onclick="selectOption('${reason.id}', '${reason.reason}', 'reason_id')">
          ${reason.reason}
        </li>`)
      .join('');
      
    const popupHtml = `
      <div class="popup report-post">
        <div class="overlay" onclick="togglePopup(this)"></div>
        <div class="content" onclick="event.stopPropagation();">
          <h1>Report ${type}</h1>
          <p>Please let us know why you are reporting this ${type}. Your report will be reviewed by a moderator.</p>
          <form method="POST" action="/${type}/report/${postId}">
            <input type="hidden" name="_token" value="${csrfToken}">
            <div class="custom-dropdown" id="customDropdown">
              <button type="button" class="dropdown-button" onclick="toggleTagDropdown()">Select a reason</button>
              <ul class="dropdown-options" id="dropdownOptions">
                ${optionsHtml} 
              </ul>
              <input type="hidden" id="reason_id" name="reason_id" required>
            </div>
            <div class="button-group">
              <button type="button" class='cancel-btn' onclick="togglePopup(this);">Cancel</button>
              <button type="submit">Submit</button>
            </div>
          </form>
        </div>
      </div>
    `;

    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = popupHtml;

    document.body.appendChild(tempDiv);

    const overlay = tempDiv.querySelector('.overlay');
    const cancelBtn = tempDiv.querySelector('.cancel-btn');

    overlay.addEventListener('click', () => {
      document.body.removeChild(tempDiv);
    });
    cancelBtn.addEventListener('click', () => {
      document.body.removeChild(tempDiv);
    })
  } catch (e) {
    renderMessage('error', e.message);
  }
}

// Toggle popup
function togglePopup(element, parentClass = null) {
  const popup = parentClass
    ? element.closest(`.${parentClass}`)?.querySelector('.popup')
    : element.closest('.popup');
  
  let buttonupload = document.getElementById('upload-button');
  if (buttonupload && !haspic) {
      buttonupload.outerHTML ='<label for="profile-picture-input" class="file-upload-button" id="upload-button">+</label>';
  } else {
    haspic=false;
  }

  if (!popup) return;

  popup.classList.toggle('hidden');

  if (popup.classList.contains('hidden')) {
    document.body.style.overflow = 'auto'; // Allow scrolling
  } else {
    document.body.style.overflow = 'hidden'; // Prevent scrolling
  }
}

// Display search helper when search is active
function onSearchActive() {
  const helper = document.querySelector('.search-helper');
  helper.classList.remove('hidden');
}

//-------------------- tag dropdown options ------------
function toggleTagDropdown() {
  const dropdown = document.getElementById('customDropdown');
  if (!dropdown) return;
  dropdown.classList.toggle('open');
}

function selectOption(value, text, id) {
  // Set the selected text on the button
  document.querySelector('.dropdown-button').textContent = text;
  
  // Update the hidden input with the selected value
  document.getElementById(id).value = value;

  document.querySelectorAll('.dropdown-options li').forEach(option => {
    option.classList.remove('selected');
  });

  // Add the 'selected' class to the clicked option
  const selectedOption = Array.from(document.querySelectorAll('.dropdown-options li')).find(option => option.textContent === text);
  if (selectedOption) selectedOption.classList.add('selected');
  
  // Close the dropdown
  toggleTagDropdown();
}

function getSelectedTab() {
  const selectedTab = document.querySelector('.tab.selected'); // Seleciona a aba com a classe 'selected'
  if (selectedTab) {
      return selectedTab.innerText; // Retorna o texto da aba selecionada
  }
  return null; // Caso nenhuma aba esteja selecionada
}

function getSelectedTabele() {
  // Seleciona o elemento com a classe 'selected'
  const selectedTab = document.querySelector('.tab.selected');
  
  // Retorna o elemento, ou null se nenhum for encontrado
  return selectedTab;
}

async function checknext(button, banger, pagination) {
  try {
  const responsefuturo = await fetch(`/home/${banger}?page=${pagination+1}`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    }
  });

  if (!responsefuturo.ok) throw new Error(`HTTP error! status: ${responsefuturo.status}`);

  const postsfuturo = await responsefuturo.json();
  if (postsfuturo.html === "<article class=\"questions post-list\">\r\n            <p>No questions found.</p>\r\n    </article>\r\n\r\n\r\n") {
    }
  }
  catch (error) {
    renderMessage(`error`, `Error fetching ${section} questions: ${error}`);
  }
}
//checa se ta nas questions
function isQuestionPage() {
  const urlPath = window.location.pathname;

  // A expressão regular para verificar o formato "/questions/{number}".
  const regex = /^\/questions\/(\d+)$/;

  // Testa se a URL corresponde à expressão regular
  return regex.test(urlPath);
}

function isHomePage() {
  const urlPath = window.location.pathname;

  // A expressão regular para verificar o formato "/home".
  const regex = /^\/home$/;

  // Testa se o caminho da URL corresponde à expressão regular
  return regex.test(urlPath);
}
function isProfile() {
  const urlPath = window.location.pathname;
  const regex = /^\/profile(\/\d+)?$/;
  // Verifica se a URL atual é "/profile"
  return regex.test(urlPath);
}
function isNotiPage() {
  const urlPath = window.location.pathname;

  // A expressão regular para verificar o formato "/home".
  const regex = /^\/notifications$/;

  // Testa se o caminho da URL corresponde à expressão regular
  return regex.test(urlPath);
}

function isfollowedPage() {
  const urlPath = window.location.pathname;

  // A expressão regular para verificar o formato "/home".
  const regex = /^\/questions-followed$/;

  // Testa se o caminho da URL corresponde à expressão regular
  return regex.test(urlPath);
}
function isTagPage() {
  const urlPath = window.location.pathname;

  // A expressão regular para verificar o formato "/home".
  const regex = /^\/show-tags$/;

  // Testa se o caminho da URL corresponde à expressão regular
  return regex.test(urlPath);
}

// Window scroll event questions
let currentPage = 1;
let isLoading = false;
let hasMorePosts = true;

window.addEventListener('scroll', async () => {
  if (isQuestionPage()) {
    const loader = document.querySelector('.loader');
    const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
    
    if (!hasMoreAnswers) {

      loader.classList.add('hidden');
      return;
    }

    if (!isLoadinganswers && scrollTop + clientHeight >= scrollHeight - 50) {
      isLoadinganswers = true;
      loader.classList.remove('hidden');
      await loadMoreAnswers();
      loader.classList.add('hidden');
      isLoadinganswers = false;
    }

  }
  else if(isHomePage() || isProfile()){
    const loader = document.querySelector('.loader');
    const { scrollTop, scrollHeight, clientHeight } = document.documentElement;

    if (!hasMorePosts) {
      loader.classList.add('hidden');
      return;
    }

    // Trigger loading when near bottom of the page
    if (!isLoading && scrollTop + clientHeight >= scrollHeight - 50) {
      isLoading = true;
      loader.classList.remove('hidden');
      await loadMore();
      loader.classList.add('hidden');
      isLoading = false;
    }
  }
  else if (isfollowedPage()){
    const loader = document.querySelector('.loader');
    const { scrollTop, scrollHeight, clientHeight } = document.documentElement;

    if (!hasmorefollowed) {
      loader.classList.add('hidden');
      return;
    }

    // Trigger loading when near bottom of the page
    if (!isLoading && scrollTop + clientHeight >= scrollHeight - 50) {
      isLoading = true;
      loader.classList.remove('hidden');
      await loadFollowed();
      loader.classList.add('hidden');
      isLoading = false;
    }
  }
  else if (isNotiPage()){
    const loader = document.querySelector('.loader');
    const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
    if (!hasmoreNotifications) {
      loader.classList.add('hidden');
      return;
    }

    // Trigger loading when near bottom of the page
    if (!isLoading && scrollTop + clientHeight >= scrollHeight - 50) {
      isLoading = true;
      loader.classList.remove('hidden');
      await loadNotification();
      loader.classList.add('hidden');
      isLoading = false;
    }
  }
});

async function loadMore() {

  let banger = '';
  const tab = getSelectedTab();
  if (isProfile()) {
    switch (tab) {
      case "Questions":
        banger = "question";
        break;
      case "Answers":
        banger = "answer";
        break;
      default:
        banger = "roberto";
        break;
    }
    try {
      const urlPath = window.location.pathname;  
      const profileId = urlPath.match(/\/profile\/(\d+)/);  
      
      let response;
      if (profileId) {
        response = await fetch(`/api/profile/load/${banger}?page=${currentPage + 1}&id=${profileId[1]}`, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
      });
      }
      else{
        response = await fetch(`/api/profile/load/${banger}?page=${currentPage + 1}`, {
            method: 'GET',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
        });
      }
      
      
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

      const posts = await response.json();
      const postContainer = document.querySelector('.post-list');
      const tempDiv = document.createElement('div');

      if (posts.html.includes('empty')) {
        hasMorePosts = false;
      }
     
      tempDiv.innerHTML = posts.html;
      const newPostContainer = tempDiv.firstElementChild;

      postContainer.appendChild(newPostContainer);
      currentPage++;
    } catch (error) {
      renderMessage('error', `Error loading more posts: ${error}`);
    }
  }
  else{
    switch (tab) {
      case "Trending" :
        banger = 'trending';
        break;
      case 'New' :
        banger = 'new';
        break;
      case 'For You' :
        banger = 'foryou';
        break;
      default:
        banger = 'trending'
        break;
    }

    const urlParams = new URLSearchParams(window.location.search);
    const searchValue = urlParams.get('question-search') || '';
    if (searchValue) {
      banger='roberto';
    }

    try {
      const response = await fetch(`/api/home/${banger}?page=${currentPage + 1}&search=${searchValue}&filter=${filter}&filterans=${answerfilter}`, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
          },
      });

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

      const posts = await response.json();
      const postContainer = document.querySelector('.post-list');
      const tempDiv = document.createElement('div');

      if (posts.html.includes('empty')) {
        hasMorePosts = false;
      }

      tempDiv.innerHTML = posts.html;
      const newPostContainer = tempDiv.firstElementChild;

      postContainer.appendChild(newPostContainer);
      currentPage++;
    } catch (error) {
      console.error('Error loading more posts:', error);
    }
    toggleLoadMore();
  }
}

async function loadSection(page, section, request, tab) {
  try {
    currentPage = 1;
    hasMorePosts = true;

    const urlPath = window.location.pathname;  
    const profileId = urlPath.match(/\/profile\/(\d+)/);  
    let response;
   
    if (isHomePage()) {
      selected = document.querySelectorAll(".time-selected");
      alltime = document.getElementById("starttime");

      selected[0].className="time";
      alltime.className="time-selected";

      selectedfil = document.querySelectorAll(".answerfil-selected");
      all = document.getElementById("startanswerfil");

      selectedfil[0].className = "answerfil";
      all.className = "answerfil-selected";
    }


    if (profileId) {
      response = await fetch(`/api/${page}/${section}?page=${request}&id=${profileId[1]}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
        });
    } else {      
      response = await fetch(`/api/${page}/${section}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
      });
    }

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const posts = await response.json();
    
    if (posts.html.includes('empty')) {
      hasMorePosts = false;
    }
    
    // loader
    const loader = document.querySelector('.loadMore .loader');
    if (loader) {
      loader.classList.add('hidden');
    }
    
    const postContainer = isAdminCenter()
      ? document.querySelector('.content-container') 
      : document.querySelector('.post-list');

    const tempDiv = document.createElement('div'); 
    tempDiv.innerHTML = posts.html;
    const newPostContainer = tempDiv.firstElementChild;

    postContainer.replaceWith(newPostContainer);
    if (isAdminCenter()) {
      if (tab.innerText=="Users") {
        
        currentPageadmin=1;
        await loadadminpaginator();
        await checknextPagestart();
      }
      else if (tab.innerText=="Manage Tags") {
        currentPageTagsAdmin=1;
        await loadadmintagpaginator();
        await checknextPageload(); 
        
      }
      else if (tab.innerText=="Reports") {
        currentPageReports=1;
        if (await hasstart()) {
          await checknextPageloadReports(); 
          await loadpaginatorreports();
        }
        
      }
      if (tab.innerText!="Users") {
        const currentUrl = new URL(window.location);
        currentUrl.search = ''; // Remove todos os parâmetros de consulta
        history.replaceState(null, '', currentUrl);
      }
    }
    updateSelectedTab(tab);
  } catch (error) {
      renderMessage('error', error.message)
  }
  toggleLoadMore();
}

function isAdminCenter() {
  return window.location.pathname.includes('/admin-center') || window.location.pathname.includes('/api/manager/load');
}

let question;

//window scroll answers
let currentPageanswers = 1;
let isLoadinganswers = false;
let hasMoreAnswers = true;

async function loadMoreAnswers() {
  try {
    const urlPath = window.location.pathname; 
    const match = urlPath.match(/\/questions\/(\d+)/);
    if (!match) {
      throw new Error('ID da questão não encontrado na URL');
    }

    const questionId = match[1];

    const response = await fetch(`/api/questions/${questionId}/answers/${currentPageanswers + 1}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    
    const answers = await response.json();

    // Verifica se não há mais respostas
    if (!answers.length) {
      hasMoreAnswers = false;

      const loader = document.querySelector('.loadMore .loader');
      if (loader) {
        loader.classList.add('hidden');
      }
      return;
    }
    
    const answerContainer = document.querySelector('.answers-section');  
    if (!answerContainer) {
      return;
    }
    
    // Itera sobre as respostas e adiciona o HTML de cada uma ao container
    answers.forEach(async answer => {
      if (answer) {
        const tempDiv = document.createElement('div');
        const answerdiv = document.createElement('div');
        answerdiv.className='answer';
        
        const parser = new DOMParser();
        const doc = parser.parseFromString(answer, 'text/html');

        // Seleciona o elemento com a classe `content-post`
        const contentPost = doc.querySelector('.content-post');

        // Obtém o valor do atributo `data-item-id`
        const dataItemId = contentPost.getAttribute('data-item-id');

        const response1 = await fetch(`/load-coms/${dataItemId}`, {
            method: 'GET',
            headers: {
              'Content-Type': 'application/json',
            },
        });
    
        if (!response1.ok) throw new Error(`HTTP error! status: ${response.status}`);
        
        const comments = await response1.json();
        
        const tempDivcoms = document.createElement('div');
        tempDivcoms.innerHTML= comments;

        tempDiv.innerHTML = answer; // Aqui inserimos o HTML da resposta
        answerdiv.appendChild(tempDiv.firstElementChild);
        const newAnswerContainer = answerdiv;
        answerContainer.appendChild(newAnswerContainer);
        answerContainer.appendChild(tempDivcoms);
      }   
    });
      
    // Aumenta o contador de páginas para a próxima requisição
    currentPageanswers++;
  } catch (error) {
    renderMessage('error', 'Error loading more posts');
  }
}

async function checkfuturoanswers() {
  try {
    const urlPath = window.location.pathname; 
    const match = urlPath.match(/\/questions\/(\d+)/);
    if (!match) {
      throw new Error('ID da questão não encontrado na URL');
    }

    const questionId = match[1];
    const response = await fetch(`/api/questions/${questionId}/answers/${currentPageanswers +1 }`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const answers = await response.json();
    
    // Verifica se a resposta está vazia ou se não há mais respostas
    if (!answers.length) {
      hasMoreAnswers = false;
    }    
  } catch (error) {
    renderMessage('error', 'Error loading more posts:');
  }
}

// Render message
function renderMessage(type, text) {
  const content = document.querySelector('main');
  if (!content) return;

  const previousMsg = content.querySelector('.status-msg');
  if (previousMsg) {
    previousMsg.remove();
  }

  let icon = "";
  switch (type) {
    case "error" : 
      icon = "error";
      break;
    case "success" : 
      icon = "check";
      break;
    default:
      break;
  }

  const html = `
    <div class="${type} status-msg">
      <i class="material-icons">${icon}</i>
      <p>${text}</p>
    </div>
  `;
  content.innerHTML += html;

  const statusMsg = content.querySelector('.status-msg:last-child');
  if (!statusMsg) return;

  setTimeout(() => {
    statusMsg.style.opacity = '0';
  }, 3500);
}

async function toggleBanUser(event, action) {
  event.preventDefault();

  const button = event.target;
  const closestArticle = button.closest("article");
  
  if (!closestArticle) return;

  const userId = closestArticle.dataset.itemId;
  if (!userId) return;

  if (!csrfToken) csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  if (!csrfToken) return;

  try {
    const result = await fetchToggleBanUser(action, userId, csrfToken);
    renderToggleBanUser(closestArticle, userId, result);
  } catch (error) {
    renderMessage('error', 'An unexpected error occurred.');
  }
}

// Ban user 
async function fetchToggleBanUser(action, userId, csrfToken) {
  const response = await fetch(`/api/admin-center/${action}/${userId}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
    },
  });

  // Parse response
  const result = await response.json();

  // Handle error response
  if (!response.ok) {
    renderMessage('error', result.message);
  }
  return result;
}

async function renderToggleBanUser(closestTr, userId, result) {
  try {
    const response = await fetch(`/api/admin-center/actions/${userId}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });

    if (!response.ok) {
      renderMessage('error', 'Failed to fetch admin actions.');
      return;
    }

    const data = await response.json();

    const dropdown = closestTr.querySelector('.dropdown');
    if (!dropdown) return;

    const oldDropdownContent = dropdown.querySelector('.dropdown-content');
    if (oldDropdownContent) {
      oldDropdownContent.remove();
    }

    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = data.html.trim();

    const newDropdown = tempDiv.querySelector('.dropdown-content');
    if (!newDropdown) return;
    
    dropdown.appendChild(newDropdown); 

    const roleElement = closestTr.querySelector('.action-item');
    if (roleElement) {
      const newRole = result.role;
      updateRoleUI(roleElement, newRole);
    }
    
    renderMessage('success', result.message);
  } catch (error) {
    renderMessage('error', 'An unexpected error occurred.');
  }
}

function updateRoleUI(role, newRole) {
  role.textContent = newRole.charAt(0).toUpperCase() + newRole.slice(1);;
  role.className = '';
  role.classList.add('action-item', newRole);
}

profilepage = 1;

async function loadProfileSection(section, id , page, tab) {
  try {
    currentPage = 1;
    hasMorePosts = true;

    const response = await fetch(`/profile/load/${section}?page=${page}&id=${id}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const posts = await response.json();
    updateSelectedTab(tab);
  } catch (error) {
    console.error(`Error fetching ${section} questions:`, error);
  }
}

let currentPageadmin = 1;
let haspages = true;

async function loadcurrentpageadmin(pagen) {
  if (pagen) {
    currentPageadmin=pagen;
  }
  const prevButton = document.querySelector('button[onclick="prevPage()"]');
  const nextButton = document.querySelector('button[onclick="nextPage()"]');

  try {
    const urlParams = new URLSearchParams(window.location.search);
    const searchValue = urlParams.get('admin-search') || '';
    
    
    await checknextPage(searchValue);

    const response = await fetch(`/api/users?page=${currentPageadmin}&search=${searchValue}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const users = await response.json();
  
    document.querySelector('.pagina').innerHTML = '';
    document.querySelector('.pagina').innerHTML = users;
    
    if (currentPageadmin >= 2) {
      prevButton.disabled=false;
    }
    else{
      prevButton.disabled=true;
    }
    if (!haspages) {
      nextButton.disabled = true;
    } else {
      nextButton.disabled = false;
    }
    await loadadminpaginator();
  } catch (error) {
    renderMessage('error', `Error fetching questions: ${error}`);
  }
}
async function loadadminpaginator() {
  try {
    const urlParams = new URLSearchParams(window.location.search);
    const searchValue = urlParams.get('admin-search') || '';
    
    
    await checknextPage(searchValue);

    const response = await fetch(`/api/admin-center/getfooter?page=${currentPageadmin}&search=${searchValue}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const contador = await response.json();
  
    let setor = document.getElementsByClassName("paginator");
    let setor2 = document.getElementsByClassName("pag");
    setor[0].innerHTML=contador.html1;
    setor2[0].innerHTML=contador.html2;
  } catch (error) {
    renderMessage('error', `Error fetching questions: ${error}`);
  }
}

async function nextPage(){
  currentPageadmin++;
  loadcurrentpageadmin(currentPageadmin);
}

async function prevPage() {
  currentPageadmin--;
  loadcurrentpageadmin(currentPageadmin);
}

async function checknextPage(searchValue) {
  try {
    const response = await fetch(`/api/users?page=${currentPageadmin+1}&search=${searchValue}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const users = await response.json();
    if (users === '') {
      haspages= false;
    } else {
      haspages= true;
    }
  }
  catch (error) {
      console.error(`Error fetching questions:`, error);
  }
}

async function checknextPagestart() {
  const nextButton = document.getElementsByClassName('arrow-btn next');

  nextButton[0].disabled=true;
  const urlParams = new URLSearchParams(window.location.search);
  
  const searchValue = urlParams.get('admin-search') || '';  

  await checknextPage(searchValue);
  if (!haspages) {
    nextButton[0].disabled=true;
  } else{
    nextButton[0].disabled=false;
  }

};

function checksectorUsers() {
  let selected = document.getElementsByClassName("tab selected");

  if (selected[0].innerText == "Users") {
    return true;
  }
  return false;
}
function checksectorTags() {
  let selected= document.getElementsByClassName("tab selected");
  if (selected[0].innerText == "Manage Tags") {
    return true;
  }
  return false;
}

window.onload =  function() {
  const currentUrl = window.location.href;
  if (currentUrl.includes('/admin-center')) {
    currentPageadmin=1;
    loadadminpaginator();
    checknextPagestart();
  }
  if (isProfile()) {
    let tab = document.getElementsByClassName('tab selected');

    loadSection('profile/load', 'question', 1 , tab[0]);
  }
  else if (isHomePage()) {
    const urlParams = new URLSearchParams(window.location.search);
    const questionSearch = urlParams.get('question-search');

    if (questionSearch=='') {
      
      empty =document.getElementsByClassName("empty");
      
      empty[0].style.display = "none";
      currentPage = 0;
      loadMore();
    }
    else if(questionSearch !== null) {
      navbar = document.getElementsByClassName("content-nav");
      navbar[0].style.display = "none";
    }
  }
  else if (isNotiPage()) {
    toggleLoadMore();
  }
  else if(isfollowedPage()){
    toggleLoadMore();
  }
  else if(isTagPage()){
    renderPagination('','');
  }
};

window.onresize = function() {
  toggleLoadMore();
};

async function toggleLoadMore() {
  
  // document height
  const documentHeight = document.documentElement.scrollHeight;
  
  // Ajustar a altura da janela ao nível de zoom
  const adjustedWindowHeight = window.innerHeight;

  // Verifica se a altura do documento é maior que a altura da janela
  if (documentHeight <= adjustedWindowHeight) {
      // Se a página for maior, exibe o "loadMore"
      if (isQuestionPage()) {
        if (!hasMoreAnswers) {        
          return;
        }
  
        if (!isLoadinganswers) {
          isLoadinganswers = true;
          
          await loadMoreAnswers();
          
          isLoadinganswers = false;
        }
      } else if (isHomePage() || isProfile()) {
        if (!hasMorePosts) {
          
          return;
        } else{
          if (!isLoading) {
            isLoading = true;
            
            await loadMore();
            
            isLoading = false;
          }
        }
      } else if (isNotiPage()) {
        await loadNotification()
      } else if (isfollowedPage()){
        await loadFollowed();
      }
  } 
}

///////////////////////////
// Notifications
async function fetchUnreadNotifications() {
  try {
    const response = await fetch(`/api/notifications/unread-count`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const data = await response.json();

    const numberNotifications = notificationContainer?.querySelector('.number-notifications');
    const notificationCounter = numberNotifications?.querySelector('span');

    notificationCounter.innerText = data.unread_notifications_count;
    if (notificationCounter === 0) numberNotifications?.classList.add('hidden');
  }
  catch(e) {
    renderMessage('error', e);
  }
}

// Fetch notification number every 5 seconds
const notificationContainer = document.querySelector('header .notifications');
if (notificationContainer) setInterval(fetchUnreadNotifications, 5000);

///////////////////////////
// Like Post
async function toggleLikePost(type, postId) {
  try {
    if (!csrfToken) csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const response = await fetch(`/api/${type}s/${postId}/toggle-like`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
      }
    });

    if (response.status === 401) {
      const data = await response.json();
      if (data.redirect) {
        window.location.href = data.redirect;
        return;
      }
    }

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const data = await response.json();
    if (!data.success) throw new Error(data.message || 'Failed to toggle like.');

    const contentPost = document.querySelector(`.${type} .content-post[data-item-id="${postId}"]`);
    const likeContainer = contentPost.querySelector('.like');
    const likeCount = likeContainer.querySelector('.like-count');
    const likeIcon = likeContainer.querySelector('i');
    
    if (likeContainer.classList.contains('liked')) {
      likeCount.textContent--;
      likeContainer.classList.remove('liked');
      likeIcon.classList.add('material-symbols-outlined');
      likeIcon.classList.remove('material-icons');
    } else {
      likeCount.textContent++;
      likeContainer.classList.add('liked');
      likeIcon.classList.remove('material-symbols-outlined');
      likeIcon.classList.add('material-icons');
    }

  } catch(error) {
    renderMessage('error', error.message);
  }
}


///////////////////////////
// Nav bar
/*document.getElementById('toggle-nav').addEventListener('click', function () {
  const sideNav = document.querySelector('.side-nav');
  sideNav.classList.toggle('hidden');
});*/

document.getElementById('toggle-nav').addEventListener('click', function () {
  const sideNav = document.querySelector('.side-nav');
  sideNav.classList.toggle('active');
});

///////////////////////////
//Show q&a answers
document.addEventListener('DOMContentLoaded', () => {
  const questions = document.querySelectorAll('.faq-question');

  questions.forEach((question) => {
      question.addEventListener('click', () => {
          //se a pergunta não tiver a classe active, ela é adicionada - util po css
          question.classList.toggle('active');

          //abre ou fecha para mostrar a answer
          const answer = question.nextElementSibling;
          answer.style.display = answer.style.display === 'block' ? 'none' : 'block';
      });
  });
});


///////////////////////////
//Badges Pop-up
/* WITHOUT THE OVERLAY THINGY*/
function toggleBadgePopup(event, popupId) {
  event.stopPropagation();  

  const allPopups = document.querySelectorAll('.badge_popup');

  allPopups.forEach(popup => {
    if (popup.id !== popupId) {
      popup.classList.add('hidden'); 
    }
  });
 
  const popup = document.getElementById(popupId);
  if (popup) {
    popup.classList.toggle('hidden'); 
  }

  // Fechar quando é clicado fora
  document.addEventListener('click', function closePopupOutside(event) {
    const popupElement = document.getElementById(popupId);

    // Checka se é clicado fora
    if (popupElement && !popupElement.contains(event.target)) {
      popupElement.classList.add('hidden'); // Fecha o popup
    }
    
    document.removeEventListener('click', closePopupOutside);
  });
}


/*Edit Profile*/
function validateAndPreviewImage(event) {
  const file = event.target.files[0]; // Obtém o arquivo selecionado
  const errorMessage = document.getElementById('error-message');
  const uploadButton = document.getElementById('upload-button');

  // Limpa mensagens de erro e pré-visualização
  errorMessage.style.display = 'none';
  errorMessage.textContent = '';
  uploadButton.innerHTML = '+';
  uploadButton.style.backgroundColor = '#73181F';
  

  if (file) {
      const maxSize = 2 * 1024 * 1024; // 2MB como tamanho máximo
      const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];

      // Verifica o tipo do arquivo
      if (!validTypes.includes(file.type)) {
          errorMessage.style.display = 'block';
          errorMessage.textContent = 'File not supported';
          return;
      }

      // Verifica o tamanho do arquivo
      if (file.size > maxSize) {
          errorMessage.style.display = 'block';
          errorMessage.textContent = 'File too big';
          return;
      }

      // Mostra a pré-visualização
      const reader = new FileReader();
      reader.onload = function (e) {
          uploadButton.innerHTML = '';
          uploadButton.style.backgroundColor = 'transparent';
          uploadButton.style.border = 'none';

          const img = document.createElement('img');
          img.src = e.target.result;
          img.alt = 'Preview';
          img.className = 'preview-image';
          uploadButton.appendChild(img);
      };
      reader.readAsDataURL(file);
  }
} 


function handleSearch(event) {
  event.preventDefault();
  const searchQuery = document.querySelector('.search-field').value;

  if (!searchQuery) {
      alert('Please enter a search query!');
      return false;
  }

  event.target.submit();
}
let filter=4;
function filterhandle(filterTab) {
  const filters = document.querySelectorAll('.dropdown-content .time-selected');
  if (filterTab!=filters[0]) {
    filters[0].className= "time";
    filterTab.className = "time-selected";
    
    switch (filterTab.innerText){
      case "Last 24 Hours":
        filter=1;
        break;
      case "Past Week":
        filter=2;
        break;
      case "Past Month":
        filter=3;
        break;
      case "All Time":
        filter=4;
        break;
      default:
        filter=0;
        break;
    }
    
    loadfiltered();
  }
}

let answerfilter=3;
async function loadfiltered() {
  hasMorePosts=true;
  currentPage = 0;
  let banger= '';
  const tab = getSelectedTab();
   
  switch (tab) {
    case "Trending" :
      banger = 'trending';
      break;
    case 'New' :
      banger = 'new';
      break;
    case 'For You' :
      banger = 'foryou';
      break;
    default:
      banger = 'trending'
      break;
  }
  const urlParams = new URLSearchParams(window.location.search);
  const searchValue = urlParams.get('question-search') || '';
 
  if (searchValue) {
    
    banger='roberto';
  }
  
  try {
    const response = await fetch(`/api/home/${banger}?page=${currentPage + 1}&search=${searchValue}&filter=${filter}&filterans=${answerfilter}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const posts = await response.json();
   
    if (posts.html.includes('empty')) {
      hasMorePosts = false;
    }
    //loader
    const loader = document.querySelector('.loadMore .loader');
    loader.classList.add('hidden');
    
    const postContainer = document.querySelector('.post-list');

    const tempDiv = document.createElement('div'); 
    tempDiv.innerHTML = posts.html;
    const newPostContainer = tempDiv.firstElementChild;

    postContainer.replaceWith(newPostContainer);

    currentPage++;
   
  } catch (error) {
    console.error('Error loading more posts:', error);
  }
}

function filterhandleAnswer(answerfiltab){
  const filters = document.querySelector('.dropdown-content .answerfil-selected');
  
  if (answerfiltab != filters) {
    filters.className= "answerfil";
    answerfiltab.className = "answerfil-selected";
  }
  switch (answerfiltab.innerText){
    case "Answered":
      answerfilter = 1;
      break;
    case "Unanswered":
      answerfilter = 2;
      break;
    case "All":
      answerfilter = 3;
      break;
    default:
      answerfilter = 0;
      break;
  }
  loadfiltered();
}
let tagfilter;
async function filterhandleTag(tagfiltab){
  const filters = document.querySelector('.dropdown-content .tagfil-sel');
  
  if (tagfiltab != filters) {
    filters.className= "tagfil";
    tagfiltab.className = "tagfil-sel";
  }
  tagfilter=tagfiltab.innerHTML;
  
  await loadPageTags(1);
}

function getTextColor(backgroundColor) {
  // Convert the hex color to RGB
  const hex = backgroundColor.replace('#', '');
  const r = parseInt(hex.substring(0, 2), 16);
  const g = parseInt(hex.substring(2, 4), 16);
  const b = parseInt(hex.substring(4, 6), 16);

  // Calculate relative luminance 
  const luminance = 0.2126 * r + 0.7152 * g + 0.0722 * b;

  // Return black (#000) for light backgrounds and white (#FFF) for dark backgrounds
  return luminance > 128 ? '#000' : '#FFF';
}

function navigateToTagsQuestions(tagName) {
  window.location.href = `/home?question-search=(${tagName})`;
}

function openEditTag(element) {
  if (!csrfToken) csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const tagRow = element.closest('.tag');
  const tagId = tagRow.getAttribute('data-item-id');
  const tagName = tagRow.querySelector('.action-item').textContent.trim();
  const tagColor = rgbToHex(tagRow.querySelector('.tag-color').style.backgroundColor);
  
  const popupHtml = `
      <div class="popup">
          <div class="overlay" onclick="togglePopup(this)"></div>
          <div class="content" onclick="event.stopPropagation()">
              <h1>Edit Tag</h1>
              <form action="/admin-center/tag/${tagId}/update" method="POST">
                  <input type="hidden" name="_token" value="${csrfToken}">
                  <div class="add-tag-inputs">
                      <div class='form-group'>
                        <input type="text" id="name" name="name" value="${tagName}" placeholder="Networking" required>
                        <label for="name">Tag name<span class="mandatory">*</span></label>
                      </div>
                      <div class='form-group'>             
                        <input type="color" id="color-input" name="color" value="${tagColor}" oninput="updateTextColorInput(this)">    
                      </div>
                      <input type="text" id="text-color" name="textColor" value="${getTextColor(tagColor)}" hidden>
                  </div>
                  <button type="submit">Update</button>
              </form>
          </div>
      </div>
  `;

  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = popupHtml;

  document.body.appendChild(tempDiv);
}

function rgbToHex(rgb) {
  const rgbValues = rgb.match(/\d+/g);
  return `#${rgbValues.map(x => parseInt(x).toString(16).padStart(2, '0')).join('')}`;
}

function openAddTag() {
  if (!csrfToken) csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  
  const html = `
      <div class="popup">
          <div class="overlay"></div>
          <div class="content" onclick="event.stopPropagation()">
              <h1>New Tag</h1>
              <form class="form-add-tag" action="{{ route('create-tag')}}" method="POST">
                  <input type="hidden" name="_token" value="${csrfToken}">
                  <div class="add-tag-inputs">
                      <div class='form-group'>
                        <input type="text" id="name" name="name" placeholder="Networking" required>  
                        <label for="name">Tag Name<span class="mandatory">*</span></label>     
                      </div>
                      <div class='form-group'>
                        <input type="color" id="color-input" name="color" value="#DCE4E8" oninput="updateTextColorInput(this)">   
                      </div>
                      <input type="text" id="text-color" name="textColor" value="${getTextColor('#DCE4E8')}" hidden>
                  </div>
                  <button type="submit">Add</button>
              </form>
          </div>
      </div>
  `;

  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = html;

  document.body.appendChild(tempDiv);

  const overlay = tempDiv.querySelector('.overlay');

  overlay.addEventListener('click', () => {
    document.body.removeChild(tempDiv);
  });
}

function updateTextColorInput(colorInput) {
  const colorValue = colorInput.value; 
  const textColor = getTextColor(colorValue); 
  document.getElementById('text-color').value = textColor; 
}

let seenNotifications = new Set();

// Mark seen notifications as read
async function markSeenNotificationsAsRead() {
  if (seenNotifications.size === 0) return; 

  const notificationIds = Array.from(seenNotifications); 

  if (!csrfToken) csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  try {
    const response = await fetch(`/api/notifications/mark-read`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({ notification_ids: notificationIds }),
    });

    if (!response.ok) throw new Error('Failed to mark notifications as read');

    const result = await response.json();
    renderMessage('success', result.message);

    notificationIds.forEach(id => {
      const notification = document.querySelector(`.notification[data-item-id="${id}"]`);
      const unreadContainer = notification?.querySelector('.unread-indicator');
      notification?.classList.remove('unread');
      unreadContainer?.remove();
    });

    seenNotifications.clear(); 
  } catch (error) {
    renderMessage('error', error.message);
  }
}

// Observe visibility of notifications
const options = {
  root: document,
  rootMargin: "0px",
  threshold: 1.0,
};

const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    const notificationId = +entry.target.getAttribute('data-item-id');

    if (entry.isIntersecting) seenNotifications.add(notificationId);
  });
}, options);

// Attach observer to existing notifications
function attachObserverToNotifications() {
  document.querySelectorAll('.notification.unread').forEach(notification => {
    if (!notification.dataset.observed) {
      observer.observe(notification);
      notification.dataset.observed = "true"; 
    }
  });
}

// Page visibility changes
document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'hidden') {
    markSeenNotificationsAsRead();
  }
});

attachObserverToNotifications();

function deleteUser(element) {
  if (!csrfToken) csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const row = element.closest('article');
  const userId = row?.dataset.itemId;

  // Render popup
  const html = `
    <div class="popup" onclick="event.stopPropagation()">
      <div class="overlay"></div>
      <div class="content">
        <h2>Delete User</h2>
        <p>Are you sure you want to delete this user? This action cannot be undone.</p>
        <div class=button-group>
          <button onclick="togglePopup(this)">Cancel</button>
          <form method="POST" action="${userId ? `/profile/delete/${userId}` : '/profile/delete'}">
            <input type="hidden" name="_token" value="${csrfToken}">
            <button type="submit">Delete</button>
          </form>  
        </div>
      </div>
    </div>
  `

  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = html;

  document.body.appendChild(tempDiv);

  const overlay = tempDiv.querySelector('.overlay');

  overlay.addEventListener('click', () => {
    document.body.removeChild(tempDiv);
  });
}

function deleteTag(element) {
  if (!csrfToken) csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const row = element.closest('tr');
  const tagId = row?.dataset.itemId;
  
  // Render popup
  const html = `
    <div class="popup">
      <div class="overlay" onclick="togglePopup(this)"></div>
      <div class="content" onclick="event.stopPropagation()">
        <h2>Delete Tag</h2>
        <p>Are you sure you want to delete this tag? This action cannot be undone.</p>
        <div class="button-group">
          <button onclick="togglePopup(this)">Cancel</button>
          <button onclick="confirmDelete(${tagId}, this)">Delete</button> 
        </div>
      </div>
    </div>
  `

  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = html;

  document.body.appendChild(tempDiv);
}

async function confirmDelete(tagId, triggerElement) {
  if (!csrfToken) csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  try {
    const response = await fetch(`/api/admin-center/delete/${tagId}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to delete tag: ${response.statusText}`);
    }

    const data = await response.json();

    renderMessage('success', data.message);
    document.querySelector(`[data-item-id="${tagId}"]`)?.remove();
    if (triggerElement) {
      togglePopup(triggerElement);
    }
  } catch (error) {
    renderMessage('error', error.message);
  }
}
let hasserach=false;
async function submitSearch(event) {
  event.preventDefault();

  const form = document.getElementById('tag-search-form');
  const input = document.getElementById('tag-search-input');

  const query = input.value.trim();

  const route = "/api/manager/load/tags"; 
  const url = `${route}?tag-search=${encodeURIComponent(query)}`;

  try {
      // Make the fetch request to get the filtered tags
      const response = await fetch(url, {
          method: 'GET',
          headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json'
          }
      });

      if (!response.ok) {
          throw new Error(`Error: ${response.statusText}`);
      }

      const data = await response.json();
      
      const tagsList = document.querySelector('table');
      tagsList.innerHTML = data.html;
      hasserach=true;
      await loadadmintagpaginator();
      await checknextPageload();
  } catch (error) {
      renderMessage('error', error.message);
  }
}


document.querySelectorAll('.btn-view-more-comments').forEach(button => {
  button.addEventListener('click', function () {
      this.blur(); // Remove o foco do botão
  });
});

async function loadMoreComments(localanswerid, pagecomments) {
    const moreButton = document.getElementById(`more-${localanswerid}`); // Seleciona pelo ID
    moreButton.remove();
    
    const response = await fetch(`/load-coms/getCom/${localanswerid}?page=${pagecomments + 1}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
    
    const comments = await response.json();
    const commentsContainer = document.getElementById(`comments-container-${localanswerid}`);
    
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML=comments;

    commentsContainer.appendChild(tempDiv);
}

let hasmoreNotifications = true
let notificationpage = 1
async function loadNotification() {
  try {
    const response = await fetch(`/api/moreNotifications?page=${notificationpage+1}`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    }
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const notifications = await response.json();

    if (notifications.length === 0) {
      hasmoreNotifications = false;
    }

    attachObserverToNotifications();

    let place=document.getElementById("notifications");
    let tempDiv;
    tempDiv=document.createElement('div');
    tempDiv.innerHTML=notifications;
    
    place.appendChild(tempDiv);
    notificationpage++;
  } catch (error) {
      renderMessage('error', error.message)
  }
}
let hasmorefollowed=true;
let loadFollowedpage=1;
async function loadFollowed(){
  loadFollowedpage = 1;

  try {
    const response = await fetch(`/question/showmorefollowed?page=${loadFollowedpage+1}`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    }
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const followedq = await response.json();
    
    
    let place=document.getElementsByClassName("questions post-list");

    let contador=1
    Object.keys(followedq).forEach((key) => {
      let followedItem = followedq[key]; // Pega o conteúdo HTML de cada chave

      // Cria um elemento temporário para manipular o HTML
      let tempDiv = document.createElement('div');
      tempDiv.innerHTML = followedItem;

      // Adiciona o primeiro elemento do tempDiv ao container (place[0])
      if (tempDiv.firstElementChild) {
          place[0].appendChild(tempDiv.firstElementChild);
      }
      contador++;
    });
    if (contador<10) {
      hasmorefollowed = false;
    }

     loadFollowedpage++;
  } catch (error) {
      renderMessage('error', error.message)
  }
}

async function resolveReport(element, reportIds) {
  if (!csrfToken) csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  // Render popup
  const html = `
    <div class="popup">
      <div class="overlay" onclick="togglePopup(this)"></div>
      <div class="content" onclick="event.stopPropagation()">
          <h1>Resolve Report</h1>
          <p>Do you want to mark this report as solved?</p>
          <div class="button-group">
              <button onclick="togglePopup(this)">Cancel</button>
              <button class="confirm-button">Yes</button> 
          </div>
      </div>
    </div>
  `;
  
  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = html;

  document.body.appendChild(tempDiv);

  tempDiv.querySelector('.confirm-button').addEventListener('click', () => {
    const reportRow = element.closest('.report-post'); 
    confirmSolve(reportRow, reportIds, tempDiv.querySelector('.confirm-button'));
  });
  
}

async function confirmSolve(element, reportIds, triggerElement) {
  if (!csrfToken) csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
  
  if (typeof reportIds === 'string') {
    reportIds = JSON.parse(reportIds);
  }

  try {
    const response = await fetch(`/api/admin-center/resolve-report`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ report_ids: reportIds }),
    });

    if (!response.ok) {
      throw new Error(`Failed to resolve report: ${response.statusText}`);
    }

    const data = await response.json();
    renderMessage('success', data.message);

    if (triggerElement) {
      togglePopup(triggerElement);
    }
    await loadcurrentpage();
  } catch (error) {
    renderMessage('error', error.message);
  }
}

async function updateDarkMode(checkbox) {
  const isDarkMode = checkbox.checked;

  if (!csrfToken) csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  try {
    const response = await fetch(`/api/profile/dark-mode`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken, 
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        is_dark_mode: isDarkMode, 
      }),
    });

    if (!response.ok) {
      throw new Error(`Error: ${response.message}`);
    }

    const data = await response.json();

    const element = document.querySelector("input[type=checkbox]");
    element?.toggleAttribute("checked");
    
    const body = document.querySelector('body');
    body.classList.toggle('dark-mode');

    const logo = document.getElementById('logo');
    if (logo) {
      logo.src = isDarkMode 
        ? '/images/logo/logo_dark.png'  
        : '/images/logo/logo_light.png'; 
    }

    const icon = document.querySelector('.dark-mode-label i');
    const span = document.querySelector('.dark-mode-label span');
    icon.innerText = isDarkMode ? 'light_mode' : 'dark_mode';
    span.innerHTML = isDarkMode ? 'Light Mode' : 'Dark Mode';
  } catch (error) {
    renderMessage('error', error.message);
    checkbox.checked = !isDarkMode; 
  }
}
let currentPageTags=1;
async function prevPageTags() {
  currentPageTags--;
  await loadPageTags(currentPageTags);
}

async function nextPageTags() {
  currentPageTags++;
  await loadPageTags(currentPageTags);
}

async function loadPageTags(pagen) {
  currentPageTags=pagen;
  let searchie='';
  let filtersel;
  const urlParams = new URLSearchParams(window.location.search);
  const searchValue = urlParams.get('tag-search') || '';
  if (searchValue) {
    searchie=searchValue;
    console.log(searchie);
  }
  if (tagfilter){
    filtersel=tagfilter;
    console.log(filtersel);
  }
  let nextbut = document.getElementsByClassName("arrow-btn next");
  let prevbut = document.getElementsByClassName("arrow-btn prev");

  try {
    const response = await fetch(`/api/showMoreTags?page=${currentPageTags}&tag-search=${searchie}&filter=${filtersel}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const tags = await response.json();
    
    
    let divtags= document.getElementsByClassName("tags");
    
    divtags[0].innerHTML=tags;
    if (currentPageTags > 1) {
      prevbut[0].disabled = false;
    }else{
      prevbut[0].disabled = true;
    }
    if (!await hasmoreTags()) {
      nextbut[0].disabled = true;
    }
    else{
      nextbut[0].disabled = false;
    }
    await renderPagination(searchie,filtersel);

  } catch (error) {
      renderMessage('error', error.message)
  }
  
}
async function renderPagination(searchie,filtersel) {
  try {
    const response = await fetch(`/api/getTagCount?page=${currentPageTags}&tag-search=${searchie}&filter=${filtersel}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const tags = await response.json();
    let setor = document.getElementsByClassName("paginator");
    let setor2 = document.getElementsByClassName("pag");
    setor[0].innerHTML=tags.html1;
    setor2[0].innerHTML=tags.html2;

  } catch (error) {
    renderMessage('error', error.message)
  }
}

async function hasmoreTags()
{
  let searchie='';
  let filtersel;
  const urlParams = new URLSearchParams(window.location.search);
  const searchValue = urlParams.get('tag-search') || '';
  if (searchValue) {
    searchie=searchValue;
    console.log(searchie);
  }
  if (tagfilter){
    filtersel=tagfilter;
    console.log(filtersel);
  }
  try {
    const response = await fetch(`/api/showMoreTags?page=${currentPageTags+1}&tag-search=${searchie}&filter=${filtersel}`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    }
    });


    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const tags = await response.json();
    if (tags==[]) {
      return false;
    }
    return true;
  } catch (error) {
    renderMessage('error', error.message)
  }
}
currentPageTagsAdmin = 1;
async function prevPageTagsAdmin() {
  currentPageTagsAdmin--;
  await loadcurrentpageadmintags(currentPageTagsAdmin);
}

async function nextPageTagsAdmin() {
  currentPageTagsAdmin++;
  await loadcurrentpageadmintags(currentPageTagsAdmin);
}

async function loadcurrentpageadmintags(pagenn) {
  if (pagenn) {
    currentPageTagsAdmin=pagenn
  }
  const searchInput = document.getElementById('tag-search-input');
  let nextbut = document.getElementsByClassName("arrow-btn next");
  let prevbut = document.getElementsByClassName("arrow-btn prev");
 
  try {
    let response;
    if (hasserach) {
      response = await fetch(`/api/admin-center/loadMoreTagsadmin?page=${currentPageTagsAdmin}&search=${searchInput.value}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
      });
      
    }else{
      response = await fetch(`/api/admin-center/loadMoreTagsadmin?page=${currentPageTagsAdmin}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
        });

    }


    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const tags = await response.json();

    let table = document.getElementsByClassName("tag-page");
    table[0].innerHTML=tags.html;
    
    
    if (currentPageTagsAdmin===1) {
      prevbut[0].disabled=true;
    }
    else{
      prevbut[0].disabled=false;
    }
    if (!await hasmoreTagsAdmin()) {
      nextbut[0].disabled=true;
    }
    else{
      nextbut[0].disabled=false;
    }
    await loadadmintagpaginator();
    
  } catch (error) {
      renderMessage('error', error.message)
  }
}
async function loadadmintagpaginator(){///api/admin-center/admintagscount
  const searchInput = document.getElementById('tag-search-input');
  let nextbut = document.getElementsByClassName("arrow-btn next");
  let prevbut = document.getElementsByClassName("arrow-btn prev");
 
  try {
    let response;
    if (hasserach) {
      response = await fetch(`/api/admin-center/admintagscount?page=${currentPageTagsAdmin}&search=${searchInput.value}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
      });
      
    }else{
      response = await fetch(`/api/admin-center/admintagscount?page=${currentPageTagsAdmin}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
        });

    }
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const contador = await response.json();
    let setor = document.getElementsByClassName("paginator");
    let setor2 =document.getElementsByClassName("pag");
    setor[0].innerHTML=contador.html1; 
    setor2[0].innerHTML=contador.html2;
  } catch (error) {
    renderMessage('error', error.message)
  }
}

async function hasmoreTagsAdmin()
{
  const searchInput = document.getElementById('tag-search-input');
  try {
    let response;
    if (hasserach) {
      response = await fetch(`/api/admin-center/loadMoreTagsadmin?page=${currentPageTagsAdmin+1}&search=${searchInput.value}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
      });
      
    }else{
      response = await fetch(`/api/admin-center/loadMoreTagsadmin?page=${currentPageTagsAdmin+1}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
        });

    }


    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const tags = await response.json();
    if (tags.html=='') {
      return false;
    }
    return true;
  } catch (error) {
    renderMessage('error', error.message)
  }
}

async function checknextPageload() {
  currentPageTagsAdmin=1;
  const searchInput = document.getElementById('tag-search-input');
  let nextbut =document.getElementsByClassName("arrow-btn next");
  let prevbut =document.getElementsByClassName("arrow-btn prev");
  prevbut[0].disabled=true;
  nextbut[0].disabled=true;

  try {
    let response;
    if (searchInput) {
      response = await fetch(`/api/admin-center/loadMoreTagsadmin?page=${2}&search=${searchInput.value}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
      });
  
    }else{
      response = await fetch(`/api/admin-center/loadMoreTagsadmin?page=${2}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        }
        });
    }


    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const tags = await response.json();
    if (tags.html=='') {
      nextbut[0].disabled=true;
    }else{
      nextbut[0].disabled=false;
    }
    
  } catch (error) {
    renderMessage('error', error.message)
  }
}

let currentPageReports=1;
async function loadcurrentpagereports(pagen) {
  if (pagen) {
    currentPageReports=pagen;
  }
  let nextbut =document.getElementsByClassName("arrow-btn next");
  let prevbut =document.getElementsByClassName("arrow-btn prev");

  try {
    const response = await fetch(`/api/admin-center/loadMorePostsadmin?page=${currentPageReports}`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    }
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const reports = await response.json();
    
    let reportspage= document.getElementsByClassName("reports-page");
    reportspage[0].innerHTML=reports.html;
    
    if (currentPageReports == 1) {
      prevbut[0].disabled=true;
    }
    else{
      prevbut[0].disabled=false;
    }
    
    if (!await hasmoreReports()) {
      nextbut[0].disabled=true;
    }
    else{
      nextbut[0].disabled=false;
    }

    await loadpaginatorreports();
  } catch (error) {
      renderMessage('error', error.message)
  }
}
async function loadpaginatorreports() {
  try {
    const response = await fetch(`/api/admin-center/countreports?page=${currentPageReports}`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    }
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const contador = await response.json();
    let setor = document.getElementsByClassName("paginator");
    let setor2 =document.getElementsByClassName("pag");
    setor[0].innerHTML=contador.html1;
    setor2[0].innerHTML=contador.html2;
  } catch (error) {
    renderMessage('error', error.message)
  }
}

async function prevPageReports() {
  currentPageReports--;
  loadcurrentpagereports(currentPageReports);
}

async function nextPageReports() {
  currentPageReports++;
  loadcurrentpagereports(currentPageReports);
}

async function hasmoreReports() {
  try {  
    const response = await fetch(`/api/admin-center/loadMorePostsadmin?page=${currentPageReports+1}`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    }
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const reports = await response.json();
    if (reports.html=='') {
      return false;
    }
    return true;

  } catch (error) {
    renderMessage('error', error.message)
  }
}

async function checknextPageloadReports() {
  
  let nextbut =document.getElementsByClassName("arrow-btn next");
  nextbut[0].disabled=true;

  try {
      const response = await fetch(`/api/admin-center/loadMorePostsadmin?page=${2}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
      });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const reports = await response.json();
    if (reports.html=='') {
      nextbut[0].disabled=true;
    }else{
      nextbut[0].disabled=false;
    }
    
  } catch (error) {
    renderMessage('error', error.message)
  }
}
async function hasstart() {
  try {
    const response = await fetch(`/api/admin-center/loadMorePostsadmin?page=${1}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
    });

  if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

  const reports = await response.json();
  
  if (reports.html=='') {
    
    return false;
  }else{
    return true;
  }
  } catch (error) {
    renderMessage('error', error.message)
  }
}

async function loadcurrentpage() {
  let nextbut = document.getElementsByClassName("arrow-btn next");
  let prevbut = document.getElementsByClassName("arrow-btn prev");
  
  try {
    const response = await fetch(`/api/admin-center/loadMorePostsadmin?page=${currentPageReports}`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    }
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const reports = await response.json();
    
    let reportspage= document.getElementsByClassName("reports-page");
    reportspage[0].innerHTML=reports.html;
    
    if (currentPageReports > 1) {
      prevbut[0].disabled=false;
    }
    if (!await hasmoreReports()) {
      nextbut[0].disabled=true;
    }

  } catch (error) {
      renderMessage('error', error.message)
  }
}


let cropper; // Variável global para o Cropper

function openCropPopup(event) {
    const file = event.target.files[0];
    const cropPopup = document.getElementById('crop-popup');
    const cropImage = document.getElementById('crop-image');
    const profilePicElement = document.querySelector('.edit-option.profilepic');
    togglePopup(profilePicElement, 'edit-container');
    const body =  document.body;
    
    body.style="overflow: hidden";

    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            cropImage.src = e.target.result; // Define a imagem carregada
            cropPopup.classList.remove('hidden'); // Mostra o pop-up

            // Inicializa o Cropper.js
            if (cropper) cropper.destroy(); // Destroi instância anterior, se houver
            cropper = new Cropper(cropImage, {
                aspectRatio: 1, // Proporção 1:1
                viewMode: 1,
                zoomable: true,
                scalable: true,
                movable: true,
            });
        };
        reader.readAsDataURL(file);
    }
}

function closeCropPopup() {
  const cropPopup = document.getElementById('crop-popup');
  if (cropper) cropper.destroy();
  cropPopup.classList.add('hidden'); // Esconde o pop-up
}
let haspic=false;
function saveCroppedImage() {
  const uploadButton = document.getElementById('upload-button');
  const cropPopup = document.getElementById('crop-popup');
  const fileInput = document.getElementById('profile-picture-input');
  const profilePicElement = document.querySelector('.edit-option.profilepic');
  
  if (cropper) {
    // Obter a imagem cortada como um canvas respeitando o zoom
    const croppedCanvas = cropper.getCroppedCanvas();

    // Convertê-la em blob
    croppedCanvas.toBlob((blob) => {
          const croppedFile = new File([blob], 'cropped-image.png', { type: 'image/png' });

          // Substitui o arquivo no input
          const dataTransfer = new DataTransfer();
          dataTransfer.items.add(croppedFile);
          fileInput.files = dataTransfer.files;
          // Atualiza o botão com a imagem cortada
          const url = URL.createObjectURL(blob);
          uploadButton.innerHTML = '';
          uploadButton.style.backgroundColor = 'transparent';
          uploadButton.style.border = 'none';

          const img = document.createElement('img');
          img.src = url;
          img.alt = 'Cropped Image';
          img.className = 'preview-image';
          uploadButton.appendChild(img);

      });
      closeCropPopup();
      haspic=true;
      togglePopup(profilePicElement, 'edit-container');
  }
}

const searchBar = document.getElementById('header-search');
const searchForm = document.querySelector('header .search');
const returnSearch = document.getElementById('return-search');

function toggleFullscreen() {
  searchForm.style.display = 'flex';
  searchBar?.classList.add('fullscreen');
  returnSearch?.classList.remove('hidden');
}

function removeFullscreen() {
    searchForm.style.display = 'none';
    searchBar?.classList.remove('fullscreen');
    returnSearch?.classList.add('hidden');
}

let profileTagsOffset; 
async function toggleProfileTags() {
  const showMore = document.querySelector('.show-more');
  showMore.classList.toggle('opened');

  const isOpened = showMore.classList.contains('opened');
  showMore.innerText = isOpened ? '▲ Show Less' : '▼ Show More';

  if (!isOpened) {
    const closedTags = Array.from(document.querySelectorAll('.interests .tag')).slice(profileTagsOffset);
    closedTags.forEach(tag => tag.remove());
    return;
  }

  const userId = document.getElementById('profile')?.dataset.itemId;
  profileTagsOffset = document.querySelectorAll('.interests .tag')?.length;
  
  try {
    const response = await fetch(`/api/profile/${userId}/tags?offset=${profileTagsOffset}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
    });

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const tags = await response.json();
    let tagsHtml = '';

    tags.forEach(tag => {
      const tagHtml = `<span class="action-item tag">${tag.name}</span>`;
      tagsHtml += tagHtml;
    })

    const lastTag = document.querySelector('.interests .tags .tag:last-of-type');
    lastTag?.insertAdjacentHTML('afterend', tagsHtml);
  } catch (error) {
    renderMessage('error', error.message);
  }
}

async function handleLeaderbordFilter(element) {
  const selected = document.querySelector('.time-selected');
  if (element === selected) return;

  try {
    const filter = document.querySelector('.filter');
    const filterText = filter?.querySelector('span');

    filterText.innerText = element.innerText;

    selected.classList.remove('time-selected');
    selected.classList.add('time');

    element.classList.add('time-selected');
    element.classList.remove('time');

    const filterTime = element.getAttribute('data-item-time');

    const response = await fetch(`/api/leaderboard?filter=${filterTime}`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
    });
  
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
  
    const data = await response.json();

    const tableBody = document.querySelector('tbody');
    tableBody.innerHTML = data;
  } catch(error) {
    renderMessage('error', error.message);
  }
}

function toggleFilter(element) {
  const dropdownContent = element.querySelector('.dropdown-content');
  dropdownContent.classList.remove('hidden');
}