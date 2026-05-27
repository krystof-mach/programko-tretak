import { useState, useEffect } from 'react'
import './App.css'
import Navbar from './components/Navbar'

interface FormErrors {
  company?: string;
  position?: string;
  email?: string;
  phone?: string;
}

function App() {
  const [formStatus, setFormStatus] = useState<'idle' | 'submitting' | 'success'>('idle');
  const [values, setValues] = useState({ company: '', position: '', email: '', phone: '' });
  const [errors, setErrors] = useState<FormErrors>({});
  const [isContactVisible, setIsContactVisible] = useState(false);
  const [scrollY, setScrollY] = useState(0);

  useEffect(() => {
    const handleScroll = () => setScrollY(window.scrollY);
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  useEffect(() => {
    const observer = new IntersectionObserver(
      ([entry]) => {
        setIsContactVisible(entry.isIntersecting);
      },
      { threshold: 0.2 } 
    );

    const contactSection = document.getElementById('contact');
    if (contactSection) observer.observe(contactSection);

    return () => {
      if (contactSection) observer.unobserve(contactSection);
    };
  }, []);

  useEffect(() => {
    const handleHashChange = () => {
      const hash = window.location.hash;
      if (hash === '#light') {
        document.documentElement.setAttribute('data-theme', 'light');
      } else if (hash === '#dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
      } else {
        document.documentElement.removeAttribute('data-theme');
      }
    };

    handleHashChange();
    window.addEventListener('hashchange', handleHashChange);
    return () => window.removeEventListener('hashchange', handleHashChange);
  }, []);

  const validateField = (name: string, value: string, currentValues: typeof values) => {
    let error = '';
    
    if (name === 'company' && !value) {
      error = 'Toto pole je povinné';
    } else if (name === 'email') {
      if (value) {
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
          error = 'Zadejte platnou e-mailovou adresu';
        }
      } else if (!currentValues.phone) {
        error = 'Zadejte e-mail nebo telefon';
      }
    } else if (name === 'phone') {
      if (value) {
        if (!/^(\+?\d{1,4})?[\s-]?\d{3}[\s-]?\d{3}[\s-]?\d{3,4}$/.test(value)) {
          error = 'Zadejte platné telefonní číslo';
        }
      } else if (!currentValues.email) {
        error = 'Zadejte e-mail nebo telefon';
      }
    }

    setErrors(prev => {
      const newErrors = { ...prev, [name]: error };
      if ((name === 'email' || name === 'phone') && (value || (name === 'email' ? currentValues.phone : currentValues.email))) {
        const otherField = name === 'email' ? 'phone' : 'email';
        if (newErrors[otherField] === 'Zadejte e-mail nebo telefon') {
          newErrors[otherField] = '';
        }
        if (!value && !(name === 'email' ? currentValues.phone : currentValues.email)) {
          newErrors.email = 'Zadejte e-mail nebo telefon';
          newErrors.phone = 'Zadejte e-mail nebo telefon';
        }
      }
      return newErrors;
    });

    return error;
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setValues(prev => ({ ...prev, [name]: value }));
  };

  const handleBlur = (e: React.FocusEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    validateField(name, value, values);
  };

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    let hasError = false;
    const companyError = validateField('company', values.company, values);
    const emailError = validateField('email', values.email, values);
    const phoneError = validateField('phone', values.phone, values);
    if (companyError || emailError || phoneError) hasError = true;
    if (hasError) return;
    setFormStatus('submitting');
    try {
      await new Promise(resolve => setTimeout(resolve, 1000));
      setFormStatus('success');
    } catch (error) {
      console.error('Chyba při odesílání:', error);
      alert('Něco se nepovedlo.');
      setFormStatus('idle');
    }
  };

  const scrollToProjects = (e: React.MouseEvent<HTMLAnchorElement>) => {
    e.preventDefault();
    const element = document.getElementById('projects');
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  };

  const scrollToContact = (e: React.MouseEvent<HTMLAnchorElement>) => {
    e.preventDefault();
    const element = document.getElementById('contact');
    if (element) {
      element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  };
return (
  <div className="app-wrapper">
    <Navbar />

    <main className="main-content">
      {/* Hero sekce */}
      <section id="home" className="section hero-section">
        <div 
          className="hero-background-wrapper"
          style={{ transform: `translateY(${scrollY * 0.1}px)` }}
        >
          <img 
            src="/team/fotka1.jpg" 
            alt="Hero Background" 
            className="hero-background-image"
          />
        </div>
        <div className="container relative-container">
          <h1 className="hero-title">Brzdí vás rutinní úkony?</h1>
          <p className="hero-subtitle">S pomocí integrovaného AI asistenta můžete být mnohem produktivnější</p>

          <div className="hero-actions">
            <a href="#projects" onClick={scrollToProjects} className="btn-primary">naše řešení</a>
          </div>
        </div>
      </section>
        <section id="projects" className="section cooperation-section">
          <div className="container">
            <h2 className="section-title">Jak bude vypadat spolupráce s námi</h2>
            <p className="section-subtitle">Jednoduše, transparentně a s jasným cílem.</p>
            <div className="grid-cards">
              <div className="macos-card">
                <div className="card-icon">1</div>
                <h3>Konzultace</h3>
                <p>Společně projdeme vaše nápady a potřeby. My dále vymyslíme, jak bychom vám mohli vyhovět.</p>
              </div>
              <div className="macos-card">
                <div className="card-icon">2</div>
                <h3>Rozbor potřeb a návrh řešení</h3>
                <p>Navrhneme vám, jak bychom implementovali AI do vašich systémů</p>
              </div>
              <div className="macos-card">
                <div className="card-icon">3</div>
                <h3>Implementace</h3>
                <p>Rychle a bezpečně nasadíme řešení přímo pro vás.</p>
              </div>
            </div>
          </div>
        </section>

        <section id="about" className="section team-section">
          <div className="container text-center">
            <h2 className="section-title">Náš tým</h2>
            
            <div className="team-grid">
              {[2, 3, 4, 5].map((num) => (
                <div key={num} className="team-member macos-card">
                  <div className="member-photo-wrapper">
                    <img 
                      src={`/team/fotka${num}.jpg`} 
                      alt={`Tým ${num}`} 
                      className="member-photo"
                      onError={(e) => {
                        (e.target as HTMLImageElement).style.display = 'none';
                        const parent = (e.target as HTMLElement).parentElement;
                        if (parent) {
                          const fallback = parent.querySelector('.member-placeholder');
                          if (fallback) (fallback as HTMLElement).style.display = 'flex';
                        }
                      }}
                      onLoad={(e) => {
                        (e.target as HTMLImageElement).style.display = 'block';
                        const parent = (e.target as HTMLElement).parentElement;
                        if (parent) {
                          const fallback = parent.querySelector('.member-placeholder');
                          if (fallback) (fallback as HTMLElement).style.display = 'none';
                        }
                      }}
                    />
                    <div className="member-placeholder">
                      <span>fotka{num}.jpg</span>
                    </div>
                  </div>
                  <h3>Člen týmu {num - 1}</h3>
                  <p>Specializace</p>
                </div>
              ))}
            </div>

            <div style={{ marginTop: '5rem' }}>
              <h2 className="section-title">Stavíme na open source.</h2>
              <p className="hero-subtitle" style={{ maxWidth: '600px', margin: '0 auto' }}>
                A jsme na to hrdí.
              </p>
            </div>
          </div>
        </section>

        <section id="contact" className="section contact-section">
          <div className="container form-container">
            <div className="form-wrapper macos-card">
              {formStatus === 'success' ? (
                <div className="success-message text-center">
                  <h2 className="section-title">Děkujeme!</h2>
                  <p className="form-subtitle">Vaše žádost byla odeslána. Brzy se vám ozveme.</p>
                  <button onClick={() => setFormStatus('idle')} className="btn-primary">Zpět</button>
                </div>
              ) : (
                <>
                  <h2 className="section-title" style={{ marginBottom: '8px' }}>Pro živé demo nás kontaktujte</h2>
                  <p className="form-subtitle">Zanechte nám na sebe kontakt a my se vám obratem ozveme s ukázkou.</p>
                  <form className="macos-form" onSubmit={handleSubmit} noValidate>
                    <div className="form-group">
                      <label htmlFor="company">Firma</label>
                      <input name="company" type="text" id="company" placeholder="Název vaší společnosti" required value={values.company} onChange={handleChange} onBlur={handleBlur} className={errors.company ? 'invalid' : ''} />
                      {errors.company && <span className="error-text">{errors.company}</span>}
                    </div>
                    <div className="form-group">
                      <label htmlFor="position">Pozice</label>
                      <input name="position" type="text" id="position" placeholder="Vaše pracovní pozice" value={values.position} onChange={handleChange} onBlur={handleBlur} className={errors.position ? 'invalid' : ''} />
                      {errors.position && <span className="error-text">{errors.position}</span>}
                    </div>
                    <div className="form-group">
                      <label htmlFor="email">E-mail</label>
                      <input name="email" type="email" id="email" placeholder="pracovni@email.cz" value={values.email} onChange={handleChange} onBlur={handleBlur} className={errors.email ? 'invalid' : ''} />
                      {errors.email && <span className="error-text">{errors.email}</span>}
                    </div>
                    <div className="form-group">
                      <label htmlFor="phone">Telefon</label>
                      <input name="phone" type="tel" id="phone" placeholder="+420 123 456 789" value={values.phone} onChange={handleChange} onBlur={handleBlur} className={errors.phone ? 'invalid' : ''} />
                      {errors.phone && <span className="error-text">{errors.phone}</span>}
                    </div>
                    <button type="submit" className="btn-primary form-submit" disabled={formStatus === 'submitting'}>
                      {formStatus === 'submitting' ? 'Odesílám...' : 'Odeslat žádost o demo'}
                    </button>
                  </form>
                </>
              )}
            </div>
          </div>
        </section>
      </main>

      <footer className="footer">
        <div className="container">
          <div className="footer-links">
            <a href="#">Odkaz 1</a>
            <a href="#">Odkaz 2</a>
            <a href="#">Odkaz 3</a>
            <a href="#">Odkaz 4</a>
          </div>
          <p>&copy; 2026 VITRIX. Všechna práva vyhrazena.</p>
        </div>
      </footer>

      {/* Plovoucí tlačítko */}
      <a 
        href="#contact" 
        onClick={scrollToContact}
        className={`floating-cta ${isContactVisible ? 'hidden' : ''}`}
      >
        vyzkoušet demo
      </a>
    </div>
  )
}

export default App
