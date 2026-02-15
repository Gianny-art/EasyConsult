/**
 * EasyConsult - Assistant IA et gestion des interactions
 * Gestion du chatbot d'assistance médicale et des animations
 */

// Configuration du symptôme expert IA
const SYMPTOM_ANALYZER = {
    // Base de données simple de symptômes courants
    symptoms: {
        'fièvre': {
            severity: 'high',
            recommendations: 'Consultez un médecin. Mesurez votre température, hydratez-vous bien.',
            urgency: 'immediat'
        },
        'mal de tête': {
            severity: 'medium',
            recommendations: 'Reposez-vous. Prenez un analgésique si nécessaire. Hydratez-vous.',
            urgency: 'normal'
        },
        'toux': {
            severity: 'medium',
            recommendations: 'Buvez beaucoup. Consultez si la toux persiste plus de 2 semaines.',
            urgency: 'normal'
        },
        'mal de gorge': {
            severity: 'medium',
            recommendations: 'Gargarisez vous avec de l\'eau tiède salée. Consultez si douleur intense.',
            urgency: 'normal'
        },
        'douleur abdominale': {
            severity: 'high',
            recommendations: 'Évitez manger. Consultez rapidement si douleur intense.',
            urgency: 'immediat'
        },
        'vomissements': {
            severity: 'medium',
            recommendations: 'Restez hydraté. Évitez l\'alimentation solide. Consultez un médecin.',
            urgency: 'urgent'
        },
        'diarrhée': {
            severity: 'medium',
            recommendations: 'Buvez beaucoup d\'eau. Évitez les aliments gras.',
            urgency: 'urgent'
        },
        'allergie': {
            severity: 'medium',
            recommendations: 'Évitez l\'allergène identifié. Prenez un antihistaminique si disponible.',
            urgency: 'normal'
        },
        'insomnie': {
            severity: 'low',
            recommendations: 'Établissez une routine régulière. Évitez les écrans avant le sommeil.',
            urgency: 'normal'
        },
        'fatigue': {
            severity: 'low',
            recommendations: 'Reposez-vous plus. Vérifiez votre alimentation et votre hydratation.',
            urgency: 'normal'
        }
    },

    /**
     * Analyse les symptômes saisis par l'utilisateur
     */
    analyze: function(userText) {
        const text = userText.toLowerCase().trim();
        let matchedSymptoms = [];
        let maxSeverity = 'low';
        let maxUrgency = 'normal';

        // Cherche les symptômes correspondants
        for (let symptom in this.symptoms) {
            if (text.includes(symptom)) {
                matchedSymptoms.push(this.symptoms[symptom]);
            }
        }

        if (matchedSymptoms.length === 0) {
            return {
                response: '⚠️ Je n\'ai pas reconnu de symptômes précis. Pouvez-vous être plus spécifique? (ex: fièvre, mal de tête, toux...)',
                severity: 'unknown',
                urgency: 'normal'
            };
        }

        // Détermine la sévérité maximale
        const severityLevels = { low: 1, medium: 2, high: 3 };
        maxSeverity = Object.keys(severityLevels).reduce((max, level) => {
            return severityLevels[level] > severityLevels[max] ? level : max;
        }, matchedSymptoms[0] && matchedSymptoms[0].severity ? matchedSymptoms[0].severity : 'low');

        // Détermine l'urgence maximale
        const urgencyLevels = { normal: 1, urgent: 2, immediat: 3 };
        const urgencies = matchedSymptoms.map(s => s.urgency || 'normal');
        maxUrgency = urgencies.reduce((max, level) => {
            return urgencyLevels[level] > urgencyLevels[max] ? level : max;
        });

        // Construit la réponse
        let response = '🏥 Analyse de vos symptômes:\n\n';
        
        // Symbole d'urgence
        if (maxUrgency === 'immediat') {
            response += '🚨 **URGENT** - Cherchez une aide médicale immédiatement!\n';
        } else if (maxUrgency === 'urgent') {
            response += '⚠️ **ATTENTION** - Consultez un médecin rapidement\n';
        }

        response += '\n📋 Recommandations:\n';
        matchedSymptoms.forEach(symptom => {
            response += `• ${symptom.recommendations}\n`;
        });

        response += `\n💡 **Important**: Cette analyse est informative. Consultez un professionnel pour un diagnostic.\n`;

        if (maxUrgency === 'immediat') {
            response += `\n🚑 Appelez immédiatement: <a href="tel:+237612345678" style="color: #dc2626; font-weight: 600;">+237 612 345 678</a>`;
        }

        return {
            response: response,
            severity: maxSeverity,
            urgency: maxUrgency,
            symptomCount: matchedSymptoms.length
        };
    }
};

/**
 * Gestion du panneau assistant flottant
 */
const ASSISTANT_PANEL = {
    init: function() {
        const floatBtn = document.getElementById('float-assist');
        const assistPanel = document.getElementById('assist-panel');
        const closeBtn = document.querySelector('.assist-close');
        const symptomForm = document.getElementById('symptom-form');
        const symptomInput = document.getElementById('symptoms');
        const assistResult = document.getElementById('assist-result');

        // Affichage/masquage du panneau
        if (floatBtn) {
            floatBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (assistPanel) {
                    assistPanel.style.display = assistPanel.style.display === 'none' ? 'block' : 'none';
                    if (assistPanel.style.display === 'block') {
                        symptomInput?.focus();
                    }
                }
            });
        }

        // Fermeture du panneau
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                if (assistPanel) {
                    assistPanel.style.display = 'none';
                }
            });
        }

        // Envoi du formulaire
        if (symptomForm) {
            symptomForm.addEventListener('submit', (e) => {
                e.preventDefault();
                
                const userText = symptomInput?.value.trim();
                
                if (!userText) {
                    if (assistResult) {
                        assistResult.innerHTML = '📝 Veuillez décrire vos symptômes';
                    }
                    return;
                }

                // Analyse les symptômes
                const analysis = SYMPTOM_ANALYZER.analyze(userText);
                
                // Affiche le résultat
                if (assistResult) {
                    assistResult.innerHTML = `
                        <div style="line-height: 1.6; color: #1f2937;">
                            ${analysis.response}
                        </div>
                    `;

                    // Ajoute un style approprié selon l'urgence
                    assistResult.style.backgroundColor = 
                        analysis.urgency === 'immediat' ? '#fee2e2' :
                        analysis.urgency === 'urgent' ? '#fef3c7' : '#dcfce7';
                    assistResult.style.borderLeft = 
                        analysis.urgency === 'immediat' ? '4px solid #dc2626' :
                        analysis.urgency === 'urgent' ? '4px solid #f59e0b' : '4px solid #10b981';
                }

                // Sauvegarde la consultation
                ASSISTANT_PANEL.saveConsultation(userText, analysis);
                
                // Réinitialise le formulaire
                symptomForm.reset();
            });
        }

        // Ferme le panneau au clic extérieur
        document.addEventListener('click', (e) => {
            if (assistPanel && floatBtn && !assistPanel.contains(e.target) && !floatBtn.contains(e.target)) {
                assistPanel.style.display = 'none';
            }
        });
    },

    /**
     * Sauvegarde la consultation via API
     */
    saveConsultation: async function(symptoms, analysis) {
        try {
            const response = await fetch('symptom_submit.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    symptoms: symptoms,
                    urgency: analysis.urgency,
                    severity: analysis.severity
                })
            });

            if (!response.ok) {
                console.error('Erreur lors de la sauvegarde');
            }
        } catch (error) {
            console.error('Erreur réseau:', error);
        }
    }
};

/**
 * Animations fluides au chargement
 */
const ANIMATIONS = {
    init: function() {
        // Animate on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observer tous les éléments animables
        document.querySelectorAll('[data-animate]').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease-out';
            observer.observe(el);
        });
    }
};

/**
 * Gestion de l'authentification dans le localStorage
 */
const AUTH = {
    isLoggedIn: function() {
        return !!localStorage.getItem('patient_session');
    },

    logout: function() {
        localStorage.removeItem('patient_session');
    }
};

/**
 * Utilitaires
 */
const UTILS = {
    /**
     * Format un numéro de téléphone
     */
    formatPhone: function(phone) {
        return phone ? phone.replace(/(\d{2})(\d{3})(\d{3})(\d{3})/, '+$1 $2 $3 $4') : '';
    },

    /**
     * Formate une date
     */
    formatDate: function(date) {
        return new Date(date).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    },

    /**
     * Affiche une notification toast
     */
    showToast: function(message, type = 'info') {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#dc2626' : '#0066cc'};
            color: white;
            border-radius: 0.75rem;
            z-index: 1000;
            animation: slideUp 0.3s ease-out;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideDown 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
};

/**
 * Initialisation au chargement du DOM
 */
document.addEventListener('DOMContentLoaded', () => {
    // Initialise l'assistant
    ASSISTANT_PANEL.init();

    // Initialise les animations
    ANIMATIONS.init();

    // Log pour debug
    console.log('🏥 EasyConsult - Assistant IA initialisé');

    // Attach generic USSD pay handlers for elements with data-pattern or data-pattern-default
    document.querySelectorAll('[data-client]').forEach(btn => {
        if (btn._ussdAttached) return;
        btn._ussdAttached = true;
        btn.addEventListener('click', (e) => {
            const provider = document.querySelector('input[name="provider"]:checked')?.value || '';
            const patternDefault = btn.dataset.patterndefault || btn.dataset.pattern || '*150*{hospital}*{client}*{amount}#';
            const patternMTN = btn.dataset.patternMtn || btn.dataset.pattern_mtn || '';
            const patternOrange = btn.dataset.patternOrange || btn.dataset.pattern_orange || '';
            const hospital = btn.dataset.hospital || '';
            const client = btn.dataset.client || '';
            const amount = btn.dataset.amount || '';

            let pattern = patternDefault;
            if (provider === 'MTN' && patternMTN) pattern = patternMTN;
            if (provider === 'ORANGE' && patternOrange) pattern = patternOrange;

            let ussd = pattern.replace('{hospital}', hospital).replace('{client}', client).replace('{amount}', amount);
            const tel = 'tel:' + encodeURIComponent(ussd);
            window.location.href = tel;
        });
    });

    // Add decorative floating shapes if on home page
    if (document.querySelector('.hero')) {
        const shapes = ['shape-blue','shape-pink','shape-yellow'];
        shapes.forEach(cls => {
            const el = document.createElement('div');
            el.className = 'floating-shape ' + cls;
            document.body.appendChild(el);
        });
    }
});

// Export pour utilisation externe
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        ASSISTANT_PANEL,
        SYMPTOM_ANALYZER,
        ANIMATIONS,
        AUTH,
        UTILS
    };
}

