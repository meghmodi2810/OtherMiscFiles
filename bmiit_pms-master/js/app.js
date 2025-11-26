/**
 * BMIIT PMS - UI Interactions
 * Beginner-friendly JavaScript for sidebar and dropdowns
 * No frameworks needed - vanilla JS only
 */

document.addEventListener('DOMContentLoaded', function() {
  
  // === Hamburger Menu Toggle ===
  const hamburgerBtn = document.querySelector('.hamburger-btn');
  const sidebar = document.querySelector('.sidebar');
  const mainWrapper = document.querySelector('.main-wrapper');
  const sidebarOverlay = document.querySelector('.sidebar-overlay');
  
  if (hamburgerBtn && sidebar) {
    // Toggle sidebar on hamburger click
    hamburgerBtn.addEventListener('click', function() {
      sidebar.classList.toggle('open');
      sidebar.classList.toggle('collapsed');
      mainWrapper.classList.toggle('expanded');
      
      // Show/hide overlay on mobile
      if (window.innerWidth <= 1024 && sidebarOverlay) {
        sidebarOverlay.classList.toggle('active');
      }
    });
  }
  
  // Close sidebar when clicking overlay (mobile)
  if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', function() {
      sidebar.classList.remove('open');
      sidebar.classList.add('collapsed');
      sidebarOverlay.classList.remove('active');
    });
  }
  
  // === Dropdown Menu Toggle (Click-based) ===
  const dropdownTriggers = document.querySelectorAll('.dropdown-trigger');
  
  dropdownTriggers.forEach(function(trigger) {
    trigger.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const menu = this.nextElementSibling;
      const isOpen = this.classList.contains('open');
      
      // Close all other dropdowns first
      dropdownTriggers.forEach(function(otherTrigger) {
        if (otherTrigger !== trigger) {
          otherTrigger.classList.remove('open');
          const otherMenu = otherTrigger.nextElementSibling;
          if (otherMenu) {
            otherMenu.classList.remove('open');
          }
        }
      });
      
      // Toggle current dropdown
      this.classList.toggle('open');
      if (menu) {
        menu.classList.toggle('open');
      }
    });
  });
  
  // === Close Mobile Menu on Link Click ===
  const navLinks = document.querySelectorAll('.nav-link');
  
  navLinks.forEach(function(link) {
    link.addEventListener('click', function() {
      // Only close on mobile
      if (window.innerWidth <= 1024) {
        sidebar.classList.remove('open');
        sidebar.classList.add('collapsed');
        if (sidebarOverlay) {
          sidebarOverlay.classList.remove('active');
        }
      }
    });
  });
  
  // === Handle Window Resize ===
  let resizeTimer;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
      // On desktop, remove overlay
      if (window.innerWidth > 1024 && sidebarOverlay) {
        sidebarOverlay.classList.remove('active');
      }
      
      // On mobile, ensure sidebar is collapsed by default
      if (window.innerWidth <= 1024) {
        sidebar.classList.add('collapsed');
        sidebar.classList.remove('open');
      }
    }, 250);
  });
  
  // === Initialize Sidebar State ===
  function initSidebarState() {
    if (window.innerWidth <= 1024) {
      sidebar.classList.add('collapsed');
      sidebar.classList.remove('open');
    } else {
      sidebar.classList.remove('collapsed');
      sidebar.classList.remove('open');
    }
  }
  
  // Run on load
  initSidebarState();
  
  // === Active Link Highlighting ===
  const currentPath = window.location.pathname;
  navLinks.forEach(function(link) {
    if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href'))) {
      link.classList.add('active');
    }
  });
  
  // === Smooth Scroll for Internal Links ===
  document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
      const href = this.getAttribute('href');
      if (href !== '#' && href !== '') {
        e.preventDefault();
        const target = document.querySelector(href);
        if (target) {
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      }
    });
  });
  
  // === Form Validation Helper (Optional) ===
  const forms = document.querySelectorAll('form[data-validate]');
  forms.forEach(function(form) {
    form.addEventListener('submit', function(e) {
      const requiredInputs = form.querySelectorAll('[required]');
      let isValid = true;
      
      requiredInputs.forEach(function(input) {
        if (!input.value.trim()) {
          isValid = false;
          input.style.borderColor = '#EF4444';
        } else {
          input.style.borderColor = '';
        }
      });
      
      if (!isValid) {
        e.preventDefault();
        alert('Please fill in all required fields.');
      }
    });
  });
  
  // === Create New Semester - Dynamic Semester Dropdown ===
  // Populates semester dropdown based on selected course
  const courseSelect = document.getElementById('course_id');
  const semesterSelect = document.getElementById('semester_no');
  
  if (courseSelect && semesterSelect) {
    function updateSemesters() {
      const course = courseSelect.value;
      semesterSelect.innerHTML = "";

      const placeholder = document.createElement("option");
      placeholder.value = "";
      placeholder.textContent = "--choose semester--";
      semesterSelect.appendChild(placeholder);

      if (!course) return;

      let max;
      if (course === "1") max = 6;        // B.Sc(IT)
      else if (course === "2") max = 4;   // M.Sc(IT)
      else if (course === "3") max = 10;  // Integrated M.Sc(IT)

      for (let i = 1; i <= max; i++) {
        const opt = document.createElement("option");
        opt.value = i;
        opt.textContent = "Semester " + i;
        semesterSelect.appendChild(opt);
      }
    }
    
    // Initialize on page load
    updateSemesters();
    
    // Update when course changes
    courseSelect.addEventListener("change", updateSemesters);
  }
  
  // === Add Student - Dynamic Class/Division Dropdown ===
  // Populates division dropdown based on selected semester
  const studentSemesterSelect = document.getElementById('student_semester_id');
  const studentClassSelect = document.getElementById('student_class');
  
  if (studentSemesterSelect && studentClassSelect) {
    // Classes data is injected by PHP as window.classesData
    if (typeof window.classesData !== 'undefined') {
      studentSemesterSelect.addEventListener("change", function () {
        const semId = this.value;
        studentClassSelect.innerHTML = "<option value=''>--choose division--</option>";

        window.classesData.forEach(function(c) {
          if (c.semester_id === semId) {
            const opt = document.createElement("option");
            opt.value = c.id;
            opt.textContent = c.name;
            studentClassSelect.appendChild(opt);
          }
        });
      });
    }
  }
  
});
