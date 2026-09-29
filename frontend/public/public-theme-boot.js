(function () {
  try {
    var theme = localStorage.getItem('paginium-public-theme');
    if (theme === 'dark') {
      document.documentElement.classList.add('dark');
    } else if (theme === 'light') {
      document.documentElement.classList.remove('dark');
    }
  } catch (e) {
    /* ignore */
  }
})();
