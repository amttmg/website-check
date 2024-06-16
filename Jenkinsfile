pipeline {
    agent any
    stages {
        stage('env-setup') {
            steps {
                sh 'cp -n .env.example .env'
            }
        }
        stage('docker-build') {
            steps {
                sh 'docker compose up -d --build'
            }
        }
        stage('project-build') {
            steps {
                sh 'docker exec workspace composer install'
                sh 'docker exec workspace npm install'
                sh 'docker exec workspace npm run prod'
                sh 'docker exec workspace chmod -R 777 storage/'
            }
        }
        stage('database-setup') {
            steps {
                sh 'docker exec workspace php artisan key:generate'
                sh 'docker exec workspace php artisan migrate'
                sh 'docker exec workspace php artisan db:seed'
            }
        }
    }
    post { 
        success {
            mail to: 'amt.tmg@gmail.com',
            subject: "Success",
            body: "Job '${JOB_NAME}' is Successfully Build on #${BUILD_NUMBER}"
        }
        failure {
            mail to: 'amt.tmg@gmail.com',
            subject: "Failed",
             body: "Job '${JOB_NAME}' is build failed on #${BUILD_NUMBER}"
        }
    }
}
