// Pipeline Jenkins équivalent au workflow GitHub Actions (pour un serveur Jenkins avec Docker).
pipeline {
    agent any

    environment {
        IMAGE = "solarshare:${env.BUILD_NUMBER}"
    }

    options {
        timestamps()
        timeout(time: 30, unit: 'MINUTES')
    }

    stages {
        stage('Dépendances') {
            agent { docker { image 'composer:2'; reuseNode true } }
            steps {
                sh 'composer install --no-interaction --prefer-dist --no-progress --ignore-platform-reqs'
            }
        }

        stage('Qualité') {
            parallel {
                stage('Style (Pint)') {
                    agent { docker { image 'php:8.3-cli'; reuseNode true } }
                    steps { sh 'vendor/bin/pint --test' }
                }
                stage('Analyse statique (Larastan)') {
                    agent { docker { image 'php:8.3-cli'; reuseNode true } }
                    steps { sh 'php -d memory_limit=1G vendor/bin/phpstan analyse --no-progress' }
                }
            }
        }

        stage('Tests') {
            agent { docker { image 'php:8.3-cli'; reuseNode true } }
            steps {
                sh '''
                    cp .env.example .env
                    php artisan key:generate
                    php artisan test --log-junit=junit.xml
                '''
            }
            post {
                always { junit 'junit.xml' }
            }
        }

        stage('SonarQube') {
            when { expression { return env.SONAR_HOST_URL } }
            steps {
                withSonarQubeEnv('sonarqube') {
                    sh 'sonar-scanner'
                }
            }
        }

        stage('Image Docker') {
            steps {
                sh 'docker build -t $IMAGE -t solarshare:latest .'
            }
        }

        stage('Test de fumée') {
            steps {
                sh '''
                    echo "APP_KEY=base64:$(openssl rand -base64 32)" > .env.docker
                    docker compose --env-file .env.docker up -d --wait --wait-timeout 300 app
                    curl -fsS http://localhost:8080/up
                '''
            }
            post {
                always { sh 'docker compose --env-file .env.docker down -v || true' }
            }
        }

        stage('Publication') {
            when { branch 'main' }
            steps {
                withCredentials([usernamePassword(credentialsId: 'registry', usernameVariable: 'USER', passwordVariable: 'PASS')]) {
                    sh '''
                        echo "$PASS" | docker login ghcr.io -u "$USER" --password-stdin
                        docker tag $IMAGE ghcr.io/$USER/solarshare:latest
                        docker push ghcr.io/$USER/solarshare:latest
                    '''
                }
            }
        }
    }

    post {
        always { cleanWs() }
    }
}
