function toggleSidebar(button) {
  const parent = button.closest('.icon-closed');

  const isOpen = parent && parent.classList.contains('hidden');
  const sidebar = document.getElementById('sidebar');
  const iconCloseds = sidebar.querySelectorAll('.icon-closed');
  const iconOpens = sidebar.querySelectorAll('.icon-open');     
  const labels = sidebar.querySelectorAll('.menu-label');
  const groups = sidebar.querySelectorAll('.group');
  const allNavElements = document.querySelectorAll('[data-target]');
  //close all submenus
  allNavElements.forEach(navEl => {
    const navTargetId = navEl.getAttribute("data-target");
    const navSubmenu = document.getElementById(navTargetId);
    navEl.setAttribute("data-open", "false");
    navEl.classList.remove('bg-secondary-200', 'rounded-t-lg');
    navEl.classList.add('hover:bg-secondary-200', 'rounded-lg');
    if (navSubmenu) {
      navSubmenu.classList.add('hidden');
    }
  });
  console.log('isOpen', isOpen == false ? 'Yes' : 'No');

    sidebar.classList.toggle('w-60');
    sidebar.classList.toggle('w-16');
    // Toggle icons
    iconCloseds.forEach(iconClosed => {
      console.log(iconClosed);
      iconClosed.classList.toggle('hidden');
    });
    
    iconOpens.forEach(iconOpen => {
      iconOpen.classList.toggle('hidden'); 
    });
    
    labels.forEach(label => {
      label.classList.toggle('hidden');
    });

    groups.forEach(group => {
      if(isOpen == false){
        console.log('open');
        //group.classList.remove('rounded-t-lg');
        group.classList.remove('px-[10px]');
        group.classList.remove('py-[6px]');
        group.classList.add('rounded-lg');        

        group.classList.remove('mb-2');
        group.classList.add('px-3');
        group.classList.add('py-2');
        group.classList.add('w-full');
      }else{
        console.log('close');
        group.classList.remove('px-3');
        group.classList.remove('py-2');
        group.classList.remove('w-full'); 
        group.classList.remove('rounded-lg'); 
        group.classList.add('mb-2');
        group.classList.add('px-[10px]');
        group.classList.add('py-[6px]');

       // group.classList.add('rounded-lg');
      }

    });

  }

  function toggleSubmenu(element) {
    const targetId = element.getAttribute("data-target");
    console.log('apply changes to ',targetId);
    const isSubmenuToggle = element.tagName.toLowerCase() === 'button';
    const parent = element.closest('.icon-closed');
    const isOpen = parent && parent.classList.contains('hidden');
    console.log('isOpen', isOpen == false ? 'No' : 'Yes');
      const sidebar = document.getElementById('sidebar');    
    if(sidebar.classList.contains('w-60') == false){
      const iconCloseds = sidebar.querySelectorAll('.icon-closed');
      const iconOpens = sidebar.querySelectorAll('.icon-open');  
      const labels = sidebar.querySelectorAll('.menu-label');
      const groups = sidebar.querySelectorAll('.group');

      iconCloseds.forEach(iconClosed => {
        console.log(iconClosed);
        iconClosed.classList.toggle('hidden');
      });
      
      iconOpens.forEach(iconOpen => {
        iconOpen.classList.toggle('hidden'); 
      });

      labels.forEach(label => {
        label.classList.toggle('hidden');
      });

      groups.forEach(group => {
          //group.classList.remove('rounded-t-lg');
          group.classList.remove('px-[10px]');
          group.classList.remove('py-[6px]');
          group.classList.add('rounded-lg');        
          group.classList.remove('mb-2');
          group.classList.add('px-3');
          group.classList.add('py-2');
          group.classList.add('w-full');
      });
      sidebar.classList.remove('w-16');
      sidebar.classList.add('w-60');
    }

    const submenu = document.getElementById(targetId);
    console.log(submenu);
  
    // Reset all nav items (buttons and links)
    const allNavElements = document.querySelectorAll('[data-target]');
    allNavElements.forEach(navEl => {
      const navTargetId = navEl.getAttribute("data-target");
      const navSubmenu = document.getElementById(navTargetId);
  
      navEl.setAttribute("data-open", "false");
      navEl.classList.remove('bg-secondary-200', 'rounded-t-lg');
      navEl.classList.add('hover:bg-secondary-200', 'rounded-lg');
  
      const darkIcon = navEl.querySelector('.dark-icon');

      if (darkIcon) {
        darkIcon.classList.remove('text-black');
        darkIcon.classList.add('text-dark-gray');
      }
      const lightIcon = navEl.querySelector('.light-icon');
      if (lightIcon) {
        lightIcon.classList.remove('text-black');
        lightIcon.classList.add('text-primary-0');
      }
  
      const arrow = navEl.querySelector('.arrow');
      if (arrow) {
        arrow.classList.remove('rotate-90');
      }
  
      if (navSubmenu) {
        navSubmenu.classList.add('hidden');
      }
    });
  
    // Apply active state to clicked item
    //element.setAttribute("data-open", "true");
    element.classList.remove('hover:bg-secondary-200');
    element.classList.add('bg-secondary-200');
  
    const darkIcon = element.querySelector('.dark-icon');
    if (darkIcon) {
      darkIcon.classList.remove('text-dark-gray');
      darkIcon.classList.add('text-black');
    }
    const lightIcon = element.querySelector('.light-icon');
    if (lightIcon) {
      lightIcon.classList.remove('text-primary-0');
      lightIcon.classList.add('text-black');
    }
    // Handle submenu toggle if applicable
    if (isSubmenuToggle && submenu) {
      const isNowOpen = !submenu.classList.contains("hidden");
      console.log('isNowOpen', !isNowOpen);
      submenu.classList.toggle("hidden", isNowOpen); // toggle class
      element.classList.toggle('rounded-t-lg', !isNowOpen);
      element.classList.toggle('rounded-lg', isNowOpen);
  
      const arrow = element.querySelector(".arrow");
      if (arrow) {
        arrow.classList.toggle("rotate-90", !isNowOpen);
      }
  
      // update open state again based on final state
      element.setAttribute("data-open", !isNowOpen);
    }
  }
  
  function doSomething(element) {
    alert('show modal');
  }