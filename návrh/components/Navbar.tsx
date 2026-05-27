import React, { useState, useEffect, useRef } from 'react';
import './Navbar.css';

const Navbar: React.FC = () => {
  const [isScrolled, setIsScrolled] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [scrollProgress, setScrollProgress] = useState(0);
  const [activeSection, setActiveSection] = useState('home');
  const [pillStyle, setPillStyle] = useState({ left: 0, top: 0, width: 0, height: 0, opacity: 0 });
  const linksRef = useRef<{ [key: string]: HTMLAnchorElement | null }>({});

  useEffect(() => {
    const handleScroll = () => {
      const scrollY = window.scrollY;
      const totalHeight = document.documentElement.scrollHeight - window.innerHeight;
      const progress = totalHeight > 0 ? scrollY / totalHeight : 0;
      
      setIsScrolled(scrollY > 20);
      setScrollProgress(progress);
    };
    
    window.addEventListener('scroll', handleScroll);
    handleScroll(); 

    const sections = document.querySelectorAll('section[id]');
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            setActiveSection(entry.target.id);
          }
        });
      },
      { rootMargin: '-20% 0px -60% 0px' }
    );

    sections.forEach((section) => observer.observe(section));

    return () => {
      window.removeEventListener('scroll', handleScroll);
      sections.forEach((section) => observer.unobserve(section));
    };
  }, []);

  useEffect(() => {
    const updatePillPosition = () => {
      const activeLink = linksRef.current[activeSection];
      if (activeLink) {
        setPillStyle({
          left: activeLink.offsetLeft,
          top: activeLink.offsetTop,
          width: activeLink.offsetWidth,
          height: activeLink.offsetHeight,
          opacity: 1
        });
      } else {
        setPillStyle(prev => ({ ...prev, opacity: 0 }));
      }
    };

    updatePillPosition();
    window.addEventListener('resize', updatePillPosition);
    return () => window.removeEventListener('resize', updatePillPosition);
  }, [activeSection, isMobileMenuOpen]);

  const toggleMobileMenu = () => {
    setIsMobileMenuOpen(!isMobileMenuOpen);
  };

  const handleNavClick = (e: React.MouseEvent<HTMLAnchorElement>, targetId: string) => {
    e.preventDefault();
    const element = document.getElementById(targetId);
    if (element) {
      const blockPosition = targetId === 'contact' ? 'center' : 'start';
      element.scrollIntoView({ behavior: 'smooth', block: blockPosition });
    } else if (targetId === 'home') {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    setIsMobileMenuOpen(false);
  };

  // Výpočet pozice: 2 cykly za celou stránku
  const startPos = -30; 
  const endPos = 130;   
  const oneCycleDistance = endPos - startPos;
  const totalDistance = oneCycleDistance * 2;
  
  const currentDistance = scrollProgress * totalDistance;
  const relativePos = currentDistance % oneCycleDistance;
  const trainLeft = startPos + relativePos;

  return (
    <>
      {isMobileMenuOpen && (
        <div className="mobile-overlay" onClick={() => setIsMobileMenuOpen(false)} />
      )}
      <nav className={`navbar ${isScrolled ? 'scrolled' : ''}`}>
        <div className="navbar-container">
          <div className="navbar-logo">
            <a href="/">
              <span className="logo-text">VITRIX<span style={{ color: 'rgb(235, 229, 70)' }}>.</span></span>
            </a>
          </div>

          <div className="navbar-nav-wrapper">
            <div className={`navbar-links ${isMobileMenuOpen ? 'active' : ''}`}>
              <div 
                className="active-pill" 
                style={{ 
                  left: `${pillStyle.left}px`, 
                  top: `${pillStyle.top}px`, 
                  width: `${pillStyle.width}px`, 
                  height: `${pillStyle.height}px`,
                  opacity: pillStyle.opacity 
                }} 
              />
              <a href="#home" ref={(el) => { linksRef.current['home'] = el; }} className={activeSection === 'home' ? 'active' : ''} onClick={(e) => handleNavClick(e, 'home')}>Domů</a>
              <a href="#projects" ref={(el) => { linksRef.current['projects'] = el; }} className={activeSection === 'projects' ? 'active' : ''} onClick={(e) => handleNavClick(e, 'projects')}>Reference</a>
              <a href="#about" ref={(el) => { linksRef.current['about'] = el; }} className={activeSection === 'about' ? 'active' : ''} onClick={(e) => handleNavClick(e, 'about')}>O nás</a>
              <a href="#contact" ref={(el) => { linksRef.current['contact'] = el; }} className={activeSection === 'contact' ? 'active' : ''} onClick={(e) => handleNavClick(e, 'contact')}>Kontakt</a>
            </div>
            
            <button className="mobile-menu-toggle" onClick={toggleMobileMenu} aria-label="Menu">
              <svg 
                className={`hamburger-svg ${isMobileMenuOpen ? 'open' : ''}`} 
                viewBox="0 0 24 24" 
                fill="none" 
                xmlns="http://www.w3.org/2000/svg"
              >
                <path className="line top" d="M4 7H20" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                <path className="line middle" d="M4 12H20" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                <path className="line bottom" d="M4 17H20" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
              </svg>
            </button>
          </div>
        </div>
        
        <div className="nav-train-track">
          <div 
            className="nav-train" 
            style={{ 
              left: `${trainLeft}%`,
              transition: 'none'
            }}
          >
            <span className="nav-train-text">ujíždí vám vlak?</span>
            <span className="nav-train-icon">
              <svg viewBox="0 -5 166 30" width="133" height="24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" style={{ overflow: 'visible' }}>
                <defs>
                  <mask id="train-mask">
                    <rect x="-10" y="-10" width="190" height="50" fill="white" />
                    {/* Okno lokomotivy (v kabině vzadu) */}
                    <rect x="109" y="7" width="7" height="6" fill="black" rx="1" />
                  </mask>
                </defs>
                <g mask="url(#train-mask)">
                  {/* Naložené znaky AI buzzwords */}
                  {/* Vagon 3 (zcela vzadu vlevo) - LLM */}
                  <text x="17" y="11" fontSize="10" fontWeight="900" fontFamily="system-ui, -apple-system, sans-serif" textAnchor="middle" fill="currentColor">LLM</text>
                  {/* Vagon 2 (uprostřed) - Agenti */}
                  <text x="53" y="11" fontSize="10" fontWeight="900" fontFamily="system-ui, -apple-system, sans-serif" textAnchor="middle" fill="currentColor">Agenti</text>
                  {/* Vagon 1 (vpravo hned za lokomotivou) - AI */}
                  <text x="89" y="11" fontSize="12" fontWeight="900" fontFamily="system-ui, -apple-system, sans-serif" textAnchor="middle" fill="currentColor">AI</text>
                  
                  {/* Vagon 3 */}
                  <rect x="5" y="14" width="24" height="5" rx="1" />
                  <circle cx="11" cy="22" r="2.5" />
                  <circle cx="23" cy="22" r="2.5" />

                  {/* Spojka mezi 3 a 2 */}
                  <rect x="29" y="16" width="4" height="2" />

                  {/* Vagon 2 */}
                  <rect x="33" y="14" width="40" height="5" rx="1" />
                  <circle cx="39" cy="22" r="2.5" />
                  <circle cx="67" cy="22" r="2.5" />

                  {/* Spojka mezi 2 a 1 */}
                  <rect x="73" y="16" width="4" height="2" />

                  {/* Vagon 1 */}
                  <rect x="77" y="14" width="24" height="5" rx="1" />
                  <circle cx="83" cy="22" r="2.5" />
                  <circle cx="95" cy="22" r="2.5" />

                  {/* Spojka mezi 1 a lokomotivou */}
                  <rect x="101" y="16" width="4" height="2" />

                  {/* Lokomotiva */}
                  {/* Kabina vzadu */}
                  <rect x="105" y="4" width="15" height="16" rx="2" />
                  {/* Kotel vpředu */}
                  <rect x="120" y="9" width="18" height="11" rx="2" />
                  {/* Komín na kotli */}
                  <rect x="129" y="3" width="5" height="6" rx="1" />
                  {/* Kolečka lokomotivy */}
                  <circle cx="112" cy="22" r="2.5" />
                  <circle cx="122" cy="22" r="2.5" />
                  <circle cx="133" cy="22" r="2.5" />
                  {/* Pluh / Cowcatcher vpředu */}
                  <polygon points="138,20 144,22 144,24 138,24" />
                </g>
              </svg>
            </span>
          </div>
        </div>
      </nav>
    </>
  );
};

export default Navbar;