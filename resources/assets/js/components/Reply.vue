<template>
    <div :id="'reply-'+id" class="panel" :class="isBest ? 'panel-success' :'panel-default' ">
        <div class="panel-heading">
            <div class="level">
                <h5 class="flex">
                    <a :href="'/profiles/'+data.owner.name" class="flex"
                    v-text="data.owner.name">
                    </a>
                    said <span v-text="ago"></span>
                </h5>

                <div v-if="singnedIn">
                    <favorite :reply="data"></favorite>
                </div>

            </div>
        </div>
        <div class="panel-body">
            <form @submit="update">
                <div v-if="editing">
                    <div class="form-group">
                        <textarea class="form-control" v-model="body" required></textarea>
                    </div>
                    <button class="btn btn-xs btn-primary">Update</button>
                    <button class="btn btn-xs btn-link" @click="editing = false" type="submit">Cancel</button>
                </div>
                <div v-else>
                    <article>
                        <div class="body" v-html="body"></div>
                    </article>
                </div>
            </form>
        </div>

        <div v-if="canUpdate" class="panel-footer level">
            <div v-if="canUpdate">
                <button class="btn btn-xs mr-1" @click="editing = true">Edit</button>
                <button class="btn btn-xs btn-danger mr-1" @click="destroy">Delete</button>
            </div>
            <div class="ml-a"><button class="btn btn-primary" @click="markAsBest" v-show="!isBest">Best reply?</button></div>
        </div>
    </div>
</template>

<script>
    import Favorite from './Favorite.vue';
    import moment from 'moment';

    export default {

        props:['data'],

        components: { Favorite },

        data(){
            return {
                editing: false,
                id: this.data.id,
                body: this.data.body,
                isBest: this.data.isBest,
            }
        },

        created(){
            window.events.$on('reply-marked-as-best', (id) =>{
               this.isBest = (id === this.id);
            });
        },

        computed: {

            ago(){
                return moment(this.data.created_at).fromNow();
            },

            singnedIn(){
                return window.App.signedIn;
            },
            canUpdate(){
                return this.authorize(user => this.data.user_id == user.id );
            }
        },

        methods:{
            update(){
                axios.patch('/replies/' + this.data.id, {
                    body: this.body
                })
                .then(data => {
                    this.editing = false;
                    flash('Updated');
                })
                .catch(error => {
                    flash(error.response.data, 'danger');
                });

            },

            destroy(){
                axios.delete('/replies/' + this.data.id);
                this.$emit('deleted', this.data.id);
            },

            markAsBest(){
                this.isBest = true;
                axios.post('/threads/'+this.id+'/best', {});
                window.events.$emit('reply-marked-as-best', this.id);
            }
        }
    }
</script>
